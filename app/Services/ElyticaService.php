<?php

namespace App\Services;

use App\Models\Food;
use Elytica\ComputeClient\ComputeService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ElyticaService
{
    protected $client;
    protected $modelPath;
    protected $configPath;
    protected $projectName = 'diet-plan';
    protected $projectId = null;
    protected $applicationId; // Elytica application ID

    public function __construct()
    {
        $token = config('services.elytica.token') ?? env('ELYTICA_TOKEN');

        if (!$token) {
            throw new \Exception('Elytica token not configured. Please set ELYTICA_TOKEN in your .env file.');
        }

        $this->applicationId = config('services.elytica.application_id') ?? env('ELYTICA_APPLICATION_ID', 14);

        $this->client = new ComputeService($token);
        $this->modelPath = base_path(env('ELYTICA_MODEL_PATH', 'app/Services/model.hlpl'));
        $this->configPath = base_path(env('ELYTICA_CONFIG_PATH', 'app/Services/model-config.json'));

        // Try to ensure project exists, but don't fail if it errors
        try {
            $this->ensureProjectExists();
        } catch (\Exception $e) {
            Log::warning('Could not ensure project exists during initialization', [
                'error' => $e->getMessage()
            ]);
            // Try to get project ID from env as fallback
            $this->projectId = (int) env('ELYTICA_PROJECT_ID');
        }
    }

    /**
     * Create a job on Elytica with the given parameters
     *
     * @param string $jobName
     * @param array $modelData
     * @return array ['job_id' => int, 'job_name' => string]
     */
    public function createJob(string $jobName, array $modelData): array
    {
        try {
            if (!$this->projectId) {
                throw new \Exception('Project ID not found. Cannot create job.');
            }

            // Step 1: Create the job first so we have a job_id
            $jobResponse = $this->client->createNewJob($this->projectId, $jobName);
            $jobId = $jobResponse->id ?? null;

            if (!$jobId) {
                throw new \Exception('Failed to create job');
            }

            Log::info('Created job', [
                'job_id' => $jobId,
                'job_name' => $jobName
            ]);

            // Step 2: Generate JSON data file with user input and food data
            $jsonData = $this->generateUserDataJSON($modelData);
            $jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // Save JSON to file for debugging
            $debugJsonPath = storage_path('app/debug_data_' . $jobId . '.json');
            file_put_contents($debugJsonPath, $jsonContent);

            Log::info('Generated user data JSON', [
                'json_size' => strlen($jsonContent),
                'debug_path' => $debugJsonPath,
                'user_data' => $modelData
            ]);

            // Step 3: Read model.hlpl from disk
            $modelContent = file_get_contents($this->modelPath);

            Log::info('Read model.hlpl file', [
                'model_path' => $this->modelPath,
                'file_size' => strlen($modelContent)
            ]);

            // Step 4: Upload model.hlpl as {job_id}.hlpl (argument 1)
            $modelFileResponse = $this->client->uploadInputFile(
                $jobId . '.hlpl',
                $modelContent,
                $this->projectId
            );

            Log::info('Upload response', [
                'response' => $modelFileResponse,
                'response_type' => gettype($modelFileResponse)
            ]);

            // Extract file ID from response - it's nested in newfiles array
            $modelFileId = null;
            if (isset($modelFileResponse->newfiles) && is_array($modelFileResponse->newfiles) && count($modelFileResponse->newfiles) > 0) {
                $modelFileId = $modelFileResponse->newfiles[0]->id ?? null;
            } elseif (isset($modelFileResponse->id)) {
                $modelFileId = $modelFileResponse->id;
            }

            if (!$modelFileId) {
                throw new \Exception('Failed to upload model file - response: ' . json_encode($modelFileResponse));
            }

            Log::info('Uploaded model file as ' . $jobId . '.hlpl', [
                'file_id' => $modelFileId,
                'job_name' => $jobName
            ]);

            // Step 5: Upload JSON data file as data.json (argument 2)
            $dataFileResponse = $this->client->uploadInputFile(
                'data.json',
                $jsonContent,
                $this->projectId
            );

            // Extract data file ID from response
            $dataFileId = null;
            if (isset($dataFileResponse->newfiles) && is_array($dataFileResponse->newfiles) && count($dataFileResponse->newfiles) > 0) {
                $dataFileId = $dataFileResponse->newfiles[0]->id ?? null;
            } elseif (isset($dataFileResponse->id)) {
                $dataFileId = $dataFileResponse->id;
            }

            if (!$dataFileId) {
                throw new \Exception('Failed to upload data file - response: ' . json_encode($dataFileResponse));
            }

            Log::info('Uploaded data file as data.json', [
                'file_id' => $dataFileId,
                'job_name' => $jobName
            ]);

            // Step 6: Assign {job_id}.hlpl to job as argument 1
            $this->client->assignFileToJob(
                $this->projectId,
                $jobId,
                $modelFileId,
                1 // Argument 1 - the model file
            );

            Log::info('Assigned ' . $jobId . '.hlpl to job', [
                'job_id' => $jobId,
                'file_id' => $modelFileId,
                'arg' => 1
            ]);

            // Step 7: Assign data.json to job as argument 2
            $this->client->assignFileToJob(
                $this->projectId,
                $jobId,
                $dataFileId,
                2 // Argument 2 - the data file
            );

            Log::info('Assigned data.json to job', [
                'job_id' => $jobId,
                'file_id' => $dataFileId,
                'arg' => 2
            ]);

            // Step 8: Queue the job for execution
            $this->client->queueJob($jobId);

            Log::info('Queued job for execution', [
                'job_id' => $jobId,
                'job_name' => $jobName
            ]);

            return [
                'job_id' => $jobId,
                'job_name' => $jobName
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Elytica job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_name' => $jobName
            ]);
            throw $e;
        }
    }

    /**
     * Get the status and result of a job
     *
     * @param int $jobId
     * @return array ['status' => string, 'data' => array|null]
     */
    public function getJobStatus(int $jobId): array
    {
        try {
            if (!$this->projectId) {
                throw new \Exception('Project ID not found');
            }

            // Get all jobs to find our job's status
            $jobs = $this->client->getJobs($this->projectId);

            if (!$jobs || !is_iterable($jobs)) {
                Log::error('Failed to get jobs list', [
                    'job_id' => $jobId,
                    'project_id' => $this->projectId,
                    'type' => gettype($jobs)
                ]);

                // Last resort: assume completed and try to get results
                Log::info('Attempting to retrieve results anyway', ['job_id' => $jobId]);
                $results = $this->getJobResults($jobId);

                if ($results) {
                    return [
                        'status' => 'completed',
                        'data' => $results
                    ];
                }

                return [
                    'status' => 'error',
                    'data' => null,
                    'error' => 'Failed to retrieve job status from Elytica'
                ];
            }

            $currentJob = null;
            foreach ($jobs as $job) {
                if ($job->id == $jobId) {
                    $currentJob = $job;
                    break;
                }
            }

            if (!$currentJob) {
                Log::warning('Job not found in project', [
                    'job_id' => $jobId,
                    'project_id' => $this->projectId
                ]);
                return [
                    'status' => 'not_found',
                    'data' => null
                ];
            }

            // Status mapping from Elytica: 0=RESET, 1=QUEUED, 2=ACCEPT, 3=PROCESS, 4=COMPLETED, 5=HALTED
            $statusMap = [
                0 => 'pending',    // RESET
                1 => 'queued',     // QUEUED
                2 => 'running',    // ACCEPT
                3 => 'running',    // PROCESS
                4 => 'completed',  // COMPLETED
                5 => 'failed'      // HALTED
            ];

            $status = $statusMap[$currentJob->status] ?? 'unknown';

            Log::info('Job status checked', [
                'job_id' => $jobId,
                'status' => $status,
                'raw_status' => $currentJob->status,
                'failure_reason' => $currentJob->failure_reason ?? null
            ]);

            // If failed, log the failure reason and try to get output logs
            if ($status === 'failed') {
                // Try to get output files for debugging
                $errorDetails = $currentJob->failure_reason ?? 'No failure reason provided';

                try {
                    $outputFiles = $this->client->getOutputFiles($jobId, $this->projectId);
                    if ($outputFiles && is_iterable($outputFiles) && (is_countable($outputFiles) && count($outputFiles) > 0)) {
                        foreach ($outputFiles as $file) {
                            // Download error logs if available
                            $tempFile = tempnam(sys_get_temp_dir(), 'elytica_error_');
                            $this->client->downloadFile($this->projectId, $file->id, $tempFile);
                            $contents = file_get_contents($tempFile);
                            unlink($tempFile);

                            Log::error('Elytica job output file', [
                                'job_id' => $jobId,
                                'filename' => $file->filename,
                                'contents' => substr($contents, 0, 1000) // First 1000 chars
                            ]);

                            // Append output to error details
                            $errorDetails .= "\n\nOutput: " . substr($contents, 0, 500);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not retrieve error output files', [
                        'job_id' => $jobId,
                        'error' => $e->getMessage()
                    ]);
                }

                Log::error('Elytica job failed', [
                    'job_id' => $jobId,
                    'failure_reason' => $errorDetails,
                    'raw_job' => $currentJob
                ]);

                return [
                    'status' => 'failed',
                    'data' => null,
                    'error' => $errorDetails
                ];
            }

            // If completed, get the output files
            if ($status === 'completed') {
                $results = $this->getJobResults($jobId);
                return [
                    'status' => 'completed',
                    'data' => $results
                ];
            }

            return [
                'status' => $status,
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get job status', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);
            throw $e;
        }
    }

    /**
     * Get the results of a completed job
     *
     * @param int $jobId
     * @return array|null
     */
    protected function getJobResults(int $jobId): ?array
    {
        try {
            if (!$this->projectId) {
                Log::error('Project ID not found when getting results', ['job_id' => $jobId]);
                // Try to get project ID by finding the job
                $this->ensureProjectExists();
                if (!$this->projectId) {
                    return null;
                }
            }

            Log::info('Attempting to get output files', [
                'job_id' => $jobId,
                'project_id' => $this->projectId
            ]);

            try {
                $outputFiles = $this->client->getOutputFiles($jobId, $this->projectId);
            } catch (\Exception $e) {
                Log::error('Failed to retrieve output files', [
                    'job_id' => $jobId,
                    'project_id' => $this->projectId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }

            // Convert to array to avoid consuming iterator
            $outputFilesArray = is_iterable($outputFiles) ? iterator_to_array($outputFiles) : [];

            Log::info('Output files response', [
                'job_id' => $jobId,
                'type' => gettype($outputFiles),
                'is_iterable' => is_iterable($outputFiles),
                'count' => count($outputFilesArray),
                'files' => array_map(fn($f) => $f->filename ?? 'unknown', $outputFilesArray)
            ]);

            if (empty($outputFilesArray)) {
                Log::warning('No output files found', ['job_id' => $jobId, 'type' => gettype($outputFiles)]);
                return null;
            }

            // Find the results JSON file (prioritize "results" file, then .json files)
            foreach ($outputFilesArray as $file) {
                if ($file->filename === 'results' || strpos($file->filename, '.json') !== false) {

                    // Download the file
                    $tempFile = tempnam(sys_get_temp_dir(), 'elytica_');
                    $this->client->downloadFile($this->projectId, $file->id, $tempFile);

                    $contents = file_get_contents($tempFile);
                    unlink($tempFile);

                    Log::info('Downloaded JSON file', [
                        'job_id' => $jobId,
                        'file_id' => $file->id,
                        'filename' => $file->filename,
                        'content_length' => strlen($contents),
                        'content_preview' => substr($contents, 0, 500)
                    ]);

                    $results = json_decode($contents, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('JSON decode failed for JSON file', [
                            'job_id' => $jobId,
                            'file_id' => $file->id,
                            'filename' => $file->filename,
                            'error' => json_last_error_msg()
                        ]);
                        continue; // Try next file
                    }

                    Log::info('Job results retrieved from JSON file', [
                        'job_id' => $jobId,
                        'file_id' => $file->id,
                        'results' => $results
                    ]);

                    return $results;
                }
            }

            // Try to extract JSON from stdout/output logs
            Log::info('No JSON file found, checking output logs', ['job_id' => $jobId]);

            if (!empty($outputFilesArray)) {
                foreach ($outputFilesArray as $file) {
                    $tempFile = tempnam(sys_get_temp_dir(), 'elytica_output_');
                    $this->client->downloadFile($this->projectId, $file->id, $tempFile);
                    $contents = file_get_contents($tempFile);
                    unlink($tempFile);

                    // Log full content for debugging
                    Log::info('Full output file content', [
                        'job_id' => $jobId,
                        'filename' => $file->filename,
                        'content' => $contents
                    ]);

                    // Look for JSON in the output (between === WRITING RESULTS === and Results written successfully)
                    // Use [\s\S]* to match any character including newlines (more reliable than .+ with /s flag)
                    if (preg_match('/===\s*WRITING RESULTS\s*===\s*\n([\s\S]+?)\n\s*Results written successfully/i', $contents, $matches)) {
                        $jsonStr = trim($matches[1]);

                        Log::info('Extracted JSON string', [
                            'job_id' => $jobId,
                            'json_length' => strlen($jsonStr),
                            'json_preview' => substr($jsonStr, 0, 200)
                        ]);

                        $results = json_decode($jsonStr, true);

                        if ($results) {
                            Log::info('Job results extracted from output logs', [
                                'job_id' => $jobId,
                                'file' => $file->filename
                            ]);
                            return $results;
                        } else {
                            Log::warning('Failed to decode JSON', [
                                'job_id' => $jobId,
                                'json_error' => json_last_error_msg(),
                                'json_string' => substr($jsonStr, 0, 500)
                            ]);
                        }
                    } else {
                        Log::info('Regex did not match in file', [
                            'job_id' => $jobId,
                            'filename' => $file->filename,
                            'has_writing_results' => strpos($contents, 'WRITING RESULTS') !== false,
                            'has_results_written' => strpos($contents, 'Results written successfully') !== false
                        ]);
                    }
                }
            }

            Log::warning('No JSON results found in any output files', [
                'job_id' => $jobId,
                'files' => array_map(fn($f) => $f->filename ?? 'unknown', $outputFilesArray)
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get job results', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);
            return null;
        }
    }

    /**
     * Ensure the project exists on Elytica
     */
    /**
     * Ensure the project exists on Elytica
     */
    protected function ensureProjectExists(): void
    {
        // If we already have a project ID from env, use it
        $envProjectId = (int) env('ELYTICA_PROJECT_ID', -1);
        if ($envProjectId > 0) {
            $this->projectId = $envProjectId;
            Log::info('Using project ID from environment', ['project_id' => $this->projectId]);
            return;
        }

        try {
            // Get projects list
            $projects = $this->client->getProjects();

            if (!$projects || !is_iterable($projects)) {
                Log::warning('Could not retrieve projects list', [
                    'type' => gettype($projects)
                ]);
                // Don't throw, just log and continue
                return;
            }

            // Find project by name
            foreach ($projects as $project) {
                if (isset($project->name) && $project->name === $this->projectName) {
                    $this->projectId = (int) $project->id;
                    Log::info('Found existing project', [
                        'project_id' => $this->projectId,
                        'project_name' => $this->projectName
                    ]);
                    return;
                }
            }

            // Project doesn't exist, create it
            Log::info('Creating new project', [
                'project_name' => $this->projectName,
                'application_id' => $this->applicationId
            ]);

            $response = $this->client->createNewProject(
                $this->projectName,
                'Diet plan optimization project',
                $this->applicationId,
                null,
                null
            );

            if ($response && isset($response->id)) {
                $this->projectId = (int) $response->id;
                Log::info('Created new project', [
                    'project_id' => $this->projectId,
                    'project_name' => $this->projectName
                ]);
                return;
            }

            Log::warning('Could not create project');
        } catch (\Exception $e) {
            Log::error('Error in ensureProjectExists', [
                'error' => $e->getMessage()
            ]);
            // Don't throw - let it fail gracefully
        }
    }

    /**
     * Load configuration from JSON file
     *
     * @return array
     */
    protected function loadConfig(): array
    {
        if (!file_exists($this->configPath)) {
            Log::warning('Config file not found, using defaults', [
                'config_path' => $this->configPath
            ]);
            // Return default config
            return [
                'solver_settings' => [
                    'gap_limit' => 0.001,
                    'time_limit' => 60
                ]
            ];
        }

        $configContent = file_get_contents($this->configPath);
        $config = json_decode($configContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse config JSON: ' . json_last_error_msg());
        }

        return $config;
    }

    /**
     * Determine workout schedule based on activity factor
     * Returns array of 7 booleans (true = workout day, false = rest day)
     *
     * @param float $activityFactor
     * @return array
     */
    protected function getWorkoutSchedule(float $activityFactor): array
    {
        // Map activity factor to number of workout days per week
        if ($activityFactor <= 1.2) {
            // Sedentary: 0 workout days (all rest)
            return [false, false, false, false, false, false, false];
        } elseif ($activityFactor <= 1.375) {
            // Lightly active: 2 workout days (Mon, Thu)
            return [true, false, false, true, false, false, false];
        } elseif ($activityFactor <= 1.55) {
            // Moderately active: 4 workout days (Mon, Tue, Thu, Fri)
            return [true, true, false, true, true, false, false];
        } elseif ($activityFactor <= 1.725) {
            // Very active: 5 workout days (Mon-Fri)
            return [true, true, true, true, true, false, false];
        } else {
            // Extremely active: 6 workout days (Mon-Sat)
            return [true, true, true, true, true, true, false];
        }
    }

    /**
     * Generate 7 separate daily diet plans and aggregate into weekly plan
     * This approach is more reliable than simultaneous 7-day optimization
     *
     * @param array $userData User input data
     * @return array Weekly meal plan with shopping list
     */
    public function generateWeeklyMealPlan(array $userData = []): array
    {
        // Calculate metabolic values
        $weight = $userData['weight'] ?? 70;
        $height = $userData['height'] ?? 175;
        $age = $userData['age'] ?? 35;
        $gender = $userData['gender'] ?? 'male';
        $activityFactor = $userData['activity_factor'] ?? 1.55;
        $goal = $userData['goal'] ?? 0;

        $decade = floor($age / 10) - 1;
        $genderBinary = ($gender === 'male' || $gender === 1) ? 1 : 0;

        $bmrMale = 66.5 + 13.8 * $weight + 5.0 * $height - 6.8 * $age;
        $bmrFemale = 655.1 + 9.6 * $weight + 1.9 * $height - 4.7 * $age;
        $ree = 1 - 0.05 * $decade;

        $bmr = $genderBinary * $bmrMale + (1 - $genderBinary) * $bmrFemale;
        $bmr2 = $bmr * $ree;
        $tdee = $bmr2 * $activityFactor;

        // Get workout schedule
        $workoutSchedule = $this->getWorkoutSchedule($activityFactor);
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Calculate calories for each day
        $dailyCalories = [];
        for ($i = 0; $i < 7; $i++) {
            if ($workoutSchedule[$i]) {
                $dailyCalories[$i] = $tdee + ($goal * 500);
            } else {
                $restDayCalories = $bmr2 * 1.2;
                $dailyCalories[$i] = $restDayCalories + ($goal * 400);
            }
        }

        $avgTargetCalories = array_sum($dailyCalories) / 7;

        // This function will be called 7 times, once per day
        // For now, return metadata for the simplified single-day model
        return [
            'bmr' => $bmr,
            'bmr2' => $bmr2,
            'tdee' => $tdee,
            'avgTargetCalories' => $avgTargetCalories,
            'dailyCalories' => $dailyCalories,
            'workoutSchedule' => $workoutSchedule,
            'dayNames' => $dayNames
        ];
    }

    /**
     * Generate JSON data file for HLPL model
     * Contains user data, food data, metabolic calculations, and constraints
     *
     * @param array $userData User input data (weight, height, age, gender, activity_factor, goal)
     * @return array
     */
    protected function generateUserDataJSON(array $userData = []): array
    {
        // Get diet type preference
        $dietType = $userData['diet_type'] ?? 'normal';

        // ONLY use foods from actual scrapers (woolworths, checkers, crowd-sourced)
        // DO NOT use placeholder/manual data with R5 prices
        $query = Food::active()
            ->whereIn('source', ['woolworths', 'checkers', 'crowd-sourced']);

        // Filter foods based on diet type
        if ($dietType === 'vegan') {
            // Exclude all animal products
            $animalProducts = ['egg', 'salmon', 'chicken', 'beef', 'liver', 'yogurt', 'fish', 'turkey', 'pork', 'lamb', 'dairy', 'milk', 'cheese'];
            foreach ($animalProducts as $product) {
                $query->where('name', 'NOT LIKE', "%{$product}%");
            }
            Log::info('Applying vegan diet filter - excluding all animal products');
        } elseif ($dietType === 'vegetarian') {
            // Exclude meat and fish, but allow dairy and eggs
            $meatProducts = ['salmon', 'chicken', 'beef', 'liver', 'fish', 'turkey', 'pork', 'lamb', 'meat'];
            foreach ($meatProducts as $product) {
                $query->where('name', 'NOT LIKE', "%{$product}%");
            }
            Log::info('Applying vegetarian diet filter - excluding meat and fish');
        }
        // For 'normal' diet type, no filtering needed

        $foods = $query->get();
        $count = $foods->count();

        // Ensure we have real food data
        if ($count === 0) {
            Log::warning('No store food data found in database for diet type: ' . $dietType);
            throw new \Exception('No suitable foods available for your diet preference. Please try a different diet type or contact the administrator.');
        }

        Log::info('Generating HLPL model', [
            'diet_type' => $dietType,
            'food_count' => $count
        ]);

        // Extract user data with defaults
        $weight = $userData['weight'] ?? 70;
        $height = $userData['height'] ?? 175;
        $age = $userData['age'] ?? 35;
        $gender = $userData['gender'] ?? 'male';
        $activityFactor = $userData['activity_factor'] ?? 1.55;
        $goal = $userData['goal'] ?? 0;

        // Extract food data into array format for JSON
        $foodsArray = [];
        $animalProteinIndices = [];
        $animalProteinNames = ['egg', 'salmon', 'chicken', 'beef', 'liver', 'yogurt', 'fish', 'turkey', 'pork', 'lamb'];

        $index = 1; // HLPL indices start at 1
        foreach ($foods as $food) {
            $calories = round((float) $food->energy_kj / 4.184, 2);

            $foodsArray[] = [
                'name' => $food->name,
                'cost' => (float) $food->cost,
                'protein' => (float) $food->protein,
                'carbs' => (float) $food->carbs,
                'fat' => (float) $food->fat,
                'calories' => $calories,
                'fiber' => (float) $food->fiber,
                'serving_size' => $food->serving_size,
                'source' => $food->source ?? 'unknown',
                'packages' => $food->packages ?? [] // Include package information for smart shopping list
            ];

            // Check if this food is an animal protein
            $nameLower = strtolower($food->name);
            foreach ($animalProteinNames as $proteinName) {
                if (strpos($nameLower, $proteinName) !== false) {
                    $animalProteinIndices[] = $index;
                    break;
                }
            }

            $index++;
        }

        Log::info('Food data extracted', [
            'food_count' => count($foodsArray),
            'animal_protein_count' => count($animalProteinIndices),
            'diet_type' => $dietType
        ]);

        // Calculate metabolic values
        $decade = floor($age / 10) - 1;
        $genderBinary = ($gender === 'male' || $gender === 1) ? 1 : 0;

        $bmrMale = 66.5 + 13.8 * $weight + 5.0 * $height - 6.8 * $age;
        $bmrFemale = 655.1 + 9.6 * $weight + 1.9 * $height - 4.7 * $age;
        $ree = 1 - 0.05 * $decade;

        $bmr = $genderBinary * $bmrMale + (1 - $genderBinary) * $bmrFemale;
        $bmr2 = $bmr * $ree;
        $tdee = $bmr2 * $activityFactor;

        // Get workout schedule (7 days)
        $workoutSchedule = $this->getWorkoutSchedule($activityFactor);

        // Calculate calories for each day
        // Workout days: TDEE + goal adjustment
        // Rest days: Lower calories (BMR * 1.2 + goal adjustment)
        $dailyCalories = [];
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        for ($i = 0; $i < 7; $i++) {
            if ($workoutSchedule[$i]) {
                // Workout day: full TDEE
                $dailyCalories[$i] = $tdee + ($goal * 500);
            } else {
                // Rest day: lower calories (about 80% of TDEE)
                $restDayCalories = $bmr2 * 1.2;
                $dailyCalories[$i] = $restDayCalories + ($goal * 400);
            }
        }

        // Average target calories for display
        $avgTargetCalories = array_sum($dailyCalories) / 7;

        // Nutritional requirements (constraints)
        $proteinMin = 100;
        $carbsMin = 150;
        $carbsMax = 400;
        $fatMin = 30;
        $fatMax = 100;
        $fiberMin = 20;

        // Build the JSON data structure
        $jsonData = [
            'foods' => $foodsArray,
            'user' => [
                'weight' => $weight,
                'height' => $height,
                'age' => $age,
                'gender' => $gender,
                'activity_factor' => $activityFactor,
                'goal' => $goal,
                'diet_type' => $dietType
            ],
            'metabolic' => [
                'bmr' => $bmr,
                'bmr2' => $bmr2,
                'tdee' => $tdee,
                'avg_target_calories' => $avgTargetCalories,
                'daily_calories' => $dailyCalories,
                'workout_schedule' => $workoutSchedule,
                'day_names' => $dayNames
            ],
            'constraints' => [
                'protein_min' => $proteinMin,
                'carbs_min' => $carbsMin,
                'carbs_max' => $carbsMax,
                'fat_min' => $fatMin,
                'fat_max' => $fatMax,
                'fiber_min' => $fiberMin
            ],
            'animal_protein_indices' => $animalProteinIndices
        ];

        Log::info('Generated JSON data', [
            'diet_type' => $dietType,
            'food_count' => count($foodsArray),
            'animal_protein_count' => count($animalProteinIndices),
            'avg_target_calories' => $avgTargetCalories
        ]);

        return $jsonData;
    }
}
