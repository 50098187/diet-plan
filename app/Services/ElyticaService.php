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

            // Step 2: Generate model with dynamic data from database and user input
            $modelContent = $this->generateHLPLModel($modelData);

            // Save to file for debugging
            $debugPath = storage_path('app/debug_model_' . $jobId . '.hlpl');
            file_put_contents($debugPath, $modelContent);

            Log::info('Generated HLPL model with user data', [
                'file_size' => strlen($modelContent),
                'project_id' => $this->projectId,
                'upload_as' => $jobId . '.hlpl',
                'debug_path' => $debugPath,
                'user_data' => $modelData,
                'first_200_chars' => substr($modelContent, 0, 200)
            ]);

            // Step 3: Upload generated model as {job_id}.hlpl
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

            // Step 4: Assign {job_id}.hlpl to job
            $this->client->assignFileToJob(
                $this->projectId,
                $jobId,
                $modelFileId,
                1 // Argument 1
            );

            Log::info('Assigned ' . $jobId . '.hlpl to job', [
                'job_id' => $jobId,
                'file_id' => $modelFileId,
                'arg' => 1
            ]);

            // Step 5: Queue the job for execution
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
     * Generate HLPL model content dynamically from database
     * Single-day optimization (much more reliable than 7-day simultaneous)
     *
     * @param array $userData User input data (weight, height, age, gender, activity_factor, goal)
     * @return string
     */
    protected function generateHLPLModel(array $userData = []): string
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

        // Build food indices
        $indices = range(1, $count);
        $indicesStr = '{' . implode(',', $indices) . '}';

        // Extract data arrays
        $costs = [];
        $proteins = [];
        $carbs = [];
        $fats = [];
        $energies = [];
        $fibers = [];
        $foodNames = [];
        $servingSizes = [];
        $sources = [];

        $animalProteinIndices = [];
        $animalProteinNames = ['egg', 'salmon', 'chicken', 'beef', 'liver', 'yogurt', 'fish', 'turkey', 'pork', 'lamb'];

        $index = 1; // HLPL indices start at 1
        foreach ($foods as $food) {
            $costs[] = (float) $food->cost;
            $proteins[] = (float) $food->protein;
            $carbs[] = (float) $food->carbs;
            $fats[] = (float) $food->fat;
            $energies[] = round((float) $food->energy_kj / 4.184, 2);
            $fibers[] = (float) $food->fiber;
            $foodNames[] = $food->name;
            $servingSizes[] = $food->serving_size;
            $sources[] = $food->source ?? 'unknown';

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

        // Identify plant-based foods (non-animal proteins)
        $plantProteinIndices = array_diff($indices, $animalProteinIndices);

        Log::info('Food categories identified', [
            'animal_protein_indices' => $animalProteinIndices,
            'animal_proteins' => array_map(fn($i) => $foodNames[$i-1], $animalProteinIndices),
            'plant_indices' => array_values($plantProteinIndices),
            'total_foods' => $count
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

        // Format arrays for HLPL
        $costsStr = '{' . implode(', ', $costs) . '}';
        $proteinsStr = '{' . implode(', ', $proteins) . '}';
        $carbsStr = '{' . implode(', ', $carbs) . '}';
        $fatsStr = '{' . implode(', ', $fats) . '}';
        $energiesStr = '{' . implode(', ', $energies) . '}';
        $fibersStr = '{' . implode(', ', $fibers) . '}';

        // JSON arrays for Python (use JSON_UNESCAPED_SLASHES to avoid escape warnings)
        $foodNamesJson = json_encode($foodNames, JSON_UNESCAPED_SLASHES);
        $servingSizesJson = json_encode($servingSizes, JSON_UNESCAPED_SLASHES);
        $costsJson = json_encode($costs);
        $proteinsJson = json_encode($proteins);
        $carbsJson = json_encode($carbs);
        $fatsJson = json_encode($fats);
        $energiesJson = json_encode($energies);
        $fibersJson = json_encode($fibers);
        $sourcesJson = json_encode($sources);

        // Weekly plan data - convert booleans to Python format
        $dayNamesJson = json_encode($dayNames);
        $dailyCaloriesJson = json_encode(array_map(fn($c) => round($c, 2), $dailyCalories));
        // Convert PHP true/false to Python True/False
        $workoutScheduleJson = str_replace(['true', 'false'], ['True', 'False'], json_encode($workoutSchedule));

        // Nutritional requirements
        $proteinMin = 100;
        $carbsMin = 150;
        $carbsMax = 400;
        $fatMin = 30;
        $fatMax = 100;
        $fiberMin = 20;

        $genderStr = ($genderBinary == 1) ? 'Male' : 'Female';
        $goalStr = ($goal == -1) ? 'Weight Loss' : (($goal == 1) ? 'Weight Gain' : 'Maintenance');

        // Use average target calories for single-day optimization
        $targetCalories = $avgTargetCalories;

        // Day indices for 7-day model
        $dayIndices = '{1,2,3,4,5,6,7}';

        // Daily calorie targets as HLPL array
        $dailyCaloriesStr = '{' . implode(', ', array_map(fn($c) => round($c, 2), $dailyCalories)) . '}';

        // Generate HLPL subset definitions
        $animalProteinSet = !empty($animalProteinIndices) ? '{' . implode(',', $animalProteinIndices) . '}' : '{}';
        $plantProteinSet = !empty($plantProteinIndices) ? '{' . implode(',', array_values($plantProteinIndices)) . '}' : '{}';

        // Generate animal protein constraints - ONLY for "normal" diet type
        // For vegan/vegetarian diets, skip animal protein requirements
        // Note: HLPL subsets are defined above for documentation, but we use explicit indexing here
        $animalProteinConstraints = '';
        if ($dietType === 'normal' && !empty($animalProteinIndices)) {
            // Build explicit sum using the indices from ANIMAL_PROTEINS set
            $terms = [];
            foreach ($animalProteinIndices as $idx) {
                $terms[] = "isused_{" . $idx . ",d}";
            }
            $sumExpression = implode(' + ', $terms);
            // Explicit constraint with forall (working version from before)
            $animalProteinConstraints = "constr " . $sumExpression . " >= 1, forall d in DAYS;\n";

            Log::info('Generated explicit animal protein constraint for normal diet', [
                'indices' => $animalProteinIndices,
                'foods' => array_map(fn($i) => $foodNames[$i-1] ?? 'unknown', $animalProteinIndices),
                'constraint' => trim($animalProteinConstraints)
            ]);
        } else {
            Log::info('Skipping animal protein constraints', [
                'diet_type' => $dietType,
                'reason' => $dietType === 'normal' ? 'No animal proteins found' : 'Diet type does not require animal proteins'
            ]);
        }

        // Generate 7-day HLPL model with variety constraints
        $hlpl = <<<HLPL
model diet_plan
set FOODS = {$indicesStr};
set DAYS = {$dayIndices};

# Food category subsets
set ANIMAL_PROTEINS = {$animalProteinSet};
set PLANT_BASED = {$plantProteinSet};

const cost = {$costsStr}, forall f in FOODS;
const protein = {$proteinsStr}, forall f in FOODS;
const carbs = {$carbsStr}, forall f in FOODS;
const fat = {$fatsStr}, forall f in FOODS;
const calories = {$energiesStr}, forall f in FOODS;
const fiber = {$fibersStr}, forall f in FOODS;

const targetcals = {$dailyCaloriesStr}, forall d in DAYS;

# 2D decision variables: servings and usage for each food on each day
var 0<=servings<=3, forall f in FOODS, forall d in DAYS;
bin isused, forall f in FOODS, forall d in DAYS;

# Auxiliary variables for variety constraints (linearization of isused_{f,d} * isused_{f,d+1})
bin overlap12, forall f in FOODS;
bin overlap23, forall f in FOODS;
bin overlap34, forall f in FOODS;
bin overlap45, forall f in FOODS;
bin overlap56, forall f in FOODS;
bin overlap67, forall f in FOODS;

# Minimize total weekly cost
min sum_{d in DAYS}{sum_{f in FOODS}{cost_{f} * servings_{f,d}}};

# Nutritional constraints for EACH day
constr sum_{f in FOODS}{calories_{f} * servings_{f,d}} >= targetcals_{d}, forall d in DAYS;
constr sum_{f in FOODS}{protein_{f} * servings_{f,d}} >= {$proteinMin}, forall d in DAYS;
constr sum_{f in FOODS}{carbs_{f} * servings_{f,d}} >= {$carbsMin}, forall d in DAYS;
constr sum_{f in FOODS}{carbs_{f} * servings_{f,d}} <= {$carbsMax}, forall d in DAYS;
constr sum_{f in FOODS}{fat_{f} * servings_{f,d}} >= {$fatMin}, forall d in DAYS;
constr sum_{f in FOODS}{fat_{f} * servings_{f,d}} <= {$fatMax}, forall d in DAYS;
constr sum_{f in FOODS}{fiber_{f} * servings_{f,d}} >= {$fiberMin}, forall d in DAYS;

# Link servings to binary isused variable (BIDIRECTIONAL)
# If isused = 0, then servings must be 0
constr servings_{f,d} <= 3 * isused_{f,d}, forall f in FOODS, forall d in DAYS;
# If isused = 1, then servings must be >= 0.1 (ensures food is actually used)
constr servings_{f,d} >= 0.1 * isused_{f,d}, forall f in FOODS, forall d in DAYS;

# Each day must have at least 6 different foods for variety
constr sum_{f in FOODS}{isused_{f,d}} >= 6, forall d in DAYS;

# Each day must have at least one animal protein (indices in ANIMAL_PROTEINS set above)
{$animalProteinConstraints}
# Linearization of overlap between consecutive days (overlap = isused_d1 AND isused_d2)
constr overlap12_{f} >= isused_{f,1} + isused_{f,2} - 1, forall f in FOODS;
constr overlap12_{f} <= isused_{f,1}, forall f in FOODS;
constr overlap12_{f} <= isused_{f,2}, forall f in FOODS;

constr overlap23_{f} >= isused_{f,2} + isused_{f,3} - 1, forall f in FOODS;
constr overlap23_{f} <= isused_{f,2}, forall f in FOODS;
constr overlap23_{f} <= isused_{f,3}, forall f in FOODS;

constr overlap34_{f} >= isused_{f,3} + isused_{f,4} - 1, forall f in FOODS;
constr overlap34_{f} <= isused_{f,3}, forall f in FOODS;
constr overlap34_{f} <= isused_{f,4}, forall f in FOODS;

constr overlap45_{f} >= isused_{f,4} + isused_{f,5} - 1, forall f in FOODS;
constr overlap45_{f} <= isused_{f,4}, forall f in FOODS;
constr overlap45_{f} <= isused_{f,5}, forall f in FOODS;

constr overlap56_{f} >= isused_{f,5} + isused_{f,6} - 1, forall f in FOODS;
constr overlap56_{f} <= isused_{f,5}, forall f in FOODS;
constr overlap56_{f} <= isused_{f,6}, forall f in FOODS;

constr overlap67_{f} >= isused_{f,6} + isused_{f,7} - 1, forall f in FOODS;
constr overlap67_{f} <= isused_{f,6}, forall f in FOODS;
constr overlap67_{f} <= isused_{f,7}, forall f in FOODS;

# Max 2 foods can overlap between consecutive days (ensures at least 3 different foods)
constr sum_{f in FOODS}{overlap12_{f}} <= 2;
constr sum_{f in FOODS}{overlap23_{f}} <= 2;
constr sum_{f in FOODS}{overlap34_{f}} <= 2;
constr sum_{f in FOODS}{overlap45_{f}} <= 2;
constr sum_{f in FOODS}{overlap56_{f}} <= 2;
constr sum_{f in FOODS}{overlap67_{f}} <= 2;
end

import elytica
import json

def main():
    food_names = {$foodNamesJson}
    serving_sizes = {$servingSizesJson}
    costs_list = {$costsJson}
    proteins_list = {$proteinsJson}
    carbs_list = {$carbsJson}
    fats_list = {$fatsJson}
    calories_list = {$energiesJson}
    fiber_list = {$fibersJson}
    sources_list = {$sourcesJson}

    bmr = {$bmr}
    bmr2 = {$bmr2}
    tdee = {$tdee}
    target_cals = {$targetCalories}
    avg_target_cals = {$avgTargetCalories}

    day_names = {$dayNamesJson}
    daily_calories_targets = {$dailyCaloriesJson}
    workout_schedule = {$workoutScheduleJson}

    print("=== DIET OPTIMIZATION MODEL ===")
    print("Weight: {$weight}kg, Height: {$height}cm, Age: {$age}")
    print("Gender: {$genderStr}")
    print("Activity Factor: {$activityFactor}")
    print("Goal: {$goalStr}")
    print("Average Daily Target: " + str(round(avg_target_cals)) + " kcal")

    print("\\nSetting solver parameters...")
    try:
        elytica.set_gap_limit("diet_plan", 0.001)
        elytica.set_time_limit("diet_plan", 60)
        print("Solver parameters set successfully")
    except Exception as e:
        print(f"ERROR setting solver parameters: {e}")
        return 1

    print("\\nInitializing optimization model...")
    try:
        elytica.init_model("diet_plan")
        print("Model initialized successfully")
    except Exception as e:
        print(f"ERROR initializing model: {e}")
        return 1

    print("\\nSolving optimization problem...")
    try:
        elytica.run_model("diet_plan")
        print("Model solved successfully")
    except Exception as e:
        print(f"ERROR solving model: {e}")
        return 1

    print("\\nGetting optimal solution...")
    try:
        optimal_cost = elytica.get_best_primal_bound("diet_plan")
        print(f"Optimal cost: R{round(optimal_cost, 2)}")
    except Exception as e:
        print(f"ERROR getting optimal cost: {e}")
        return 1

    # Extract 7-day optimal diet
    print("\\n=== METABOLIC CALCULATIONS ===")
    print("BMR: " + str(round(bmr)) + " kcal")
    print("BMR Adjusted: " + str(round(bmr2)) + " kcal")
    print("TDEE: " + str(round(tdee)) + " kcal")
    print("Average Target: " + str(round(avg_target_cals)) + " kcal")
    print("Weekly Cost: R" + str(round(optimal_cost, 2)))

    # Initialize results structure
    results = {
        "optimal_weekly_cost": round(optimal_cost, 2),
        "bmr": round(bmr, 2),
        "bmr_adjusted": round(bmr2, 2),
        "tdee": round(tdee, 2),
        "avg_target_calories": round(avg_target_cals, 2),
        "daily_calories_targets": daily_calories_targets,
        "workout_schedule": workout_schedule,
        "day_names": day_names,
        "daily_plans": []
    }

    # Extract meal plan for each day
    for day in range(1, 8):
        day_name = day_names[day - 1]
        print(f"\\n=== {day_name.upper()} (Day {day}) ===")

        day_foods = []
        day_totals = {
            "calories": 0,
            "protein": 0,
            "carbs": 0,
            "fat": 0,
            "fiber": 0
        }
        day_cost = 0

        # Extract servings for each food on this day
        for i in range(1, {$count} + 1):
            var_name = f"servings{i},{day}"
            servings = elytica.get_variable_value("diet_plan", var_name)

            if servings > 0.01:
                food_index = i - 1
                food_cost = servings * costs_list[food_index]

                food_info = {
                    "name": food_names[food_index],
                    "servings": round(servings, 2),
                    "serving_size": serving_sizes[food_index],
                    "cost": round(food_cost, 2),
                    "source": sources_list[food_index]
                }
                day_foods.append(food_info)
                day_cost += food_cost

                # Update day totals
                day_totals["protein"] += servings * proteins_list[food_index]
                day_totals["carbs"] += servings * carbs_list[food_index]
                day_totals["fat"] += servings * fats_list[food_index]
                day_totals["calories"] += servings * calories_list[food_index]
                day_totals["fiber"] += servings * fiber_list[food_index]

                print(f"  {food_names[food_index]}: {round(servings, 2)} servings")

        # Round totals
        for key in day_totals:
            day_totals[key] = round(day_totals[key], 2)

        print(f"  Cost: R{round(day_cost, 2)}")
        print(f"  Calories: {round(day_totals['calories'])} | Protein: {round(day_totals['protein'])}g | Carbs: {round(day_totals['carbs'])}g")

        # Add to results
        results["daily_plans"].append({
            "day": day,
            "day_name": day_name,
            "foods": day_foods,
            "totals": day_totals,
            "cost": round(day_cost, 2)
        })

    # Generate weekly shopping list by summing all days
    print("\\n=== GENERATING WEEKLY SHOPPING LIST ===")
    food_weekly_servings = {}  # food_name -> total servings

    # Sum servings across all days
    for day_plan in results["daily_plans"]:
        for food in day_plan["foods"]:
            food_key = food["name"]
            if food_key not in food_weekly_servings:
                food_weekly_servings[food_key] = {
                    "servings": 0,
                    "serving_size": food["serving_size"],
                    "cost_per_serving": 0,
                    "source": food["source"]
                }
            food_weekly_servings[food_key]["servings"] += food["servings"]
            # Calculate cost per serving (should be consistent)
            food_weekly_servings[food_key]["cost_per_serving"] = food["cost"] / food["servings"]

    weekly_shopping_list = []
    weekly_total_cost = 0

    for food_name, data in food_weekly_servings.items():
        weekly_servings = data["servings"]
        weekly_cost = weekly_servings * data["cost_per_serving"]
        weekly_total_cost += weekly_cost

        # Smart packaging logic
        serving_size_lower = data["serving_size"].lower()
        smart_quantity = ""

        if "slice" in serving_size_lower:
            # Bread: 1 loaf = ~20 slices
            slices = weekly_servings
            loaves = int((slices + 19) / 20)  # Round up
            smart_quantity = f"{loaves} {'loaf' if loaves == 1 else 'loaves'} ({int(slices)} slices)"

        elif "egg" in serving_size_lower or "large egg" in serving_size_lower:
            # Eggs: dozens
            eggs = int(weekly_servings + 0.5)  # Round
            dozens = eggs // 12
            remainder = eggs % 12
            if dozens > 0 and remainder > 0:
                smart_quantity = f"{dozens} dozen + {remainder} eggs ({eggs} eggs total)"
            elif dozens > 0:
                smart_quantity = f"{dozens} {'dozen' if dozens == 1 else 'dozen'} ({eggs} eggs)"
            else:
                smart_quantity = f"{eggs} eggs"

        elif "cup" in serving_size_lower or "g" in serving_size_lower:
            # Weight-based: just show total grams
            import re
            gram_match = re.search(r'(\d+)\s*g', data["serving_size"])
            if gram_match:
                grams_per_serving = float(gram_match.group(1))
                total_grams = int(weekly_servings * grams_per_serving)
                if total_grams >= 1000:
                    kg = total_grams / 1000
                    smart_quantity = f"{kg:.1f}kg"
                else:
                    smart_quantity = f"{total_grams}g"
            else:
                smart_quantity = f"{round(weekly_servings, 1)} servings"

        else:
            # Default: just show servings
            smart_quantity = f"{round(weekly_servings, 1)} servings"

        weekly_shopping_list.append({
            "name": food_name,
            "weekly_servings": round(weekly_servings, 2),
            "smart_quantity": smart_quantity,
            "serving_size": data["serving_size"],
            "weekly_cost": round(weekly_cost, 2),
            "source": data["source"]
        })

        print(f"  {food_name}: {smart_quantity} - R{round(weekly_cost, 2)}")

    print(f"\\nTotal Weekly Cost: R{round(weekly_total_cost, 2)}")

    # Add weekly data to results
    results["optimal_weekly_cost"] = round(weekly_total_cost, 2)
    results["weekly_shopping_list"] = weekly_shopping_list

    results_json = json.dumps(results, indent=2)
    print("\\n=== WRITING RESULTS ===")
    print(results_json)

    elytica.write_results(results_json)
    print("Results written successfully")

    return 0

HLPL;

        // CRITICAL DEBUGGING: Verify animal protein constraints are in the final model
        Log::info('Final HLPL model verification', [
            'model_contains_explicit_constraint' => strpos($hlpl, 'isused_{1,1} + isused_{2,1}') !== false ? 'YES' : 'NO',
            'constraint_count' => substr_count($hlpl, 'isused_{1,'),
            'constraint_section_preview' => substr($hlpl, strpos($hlpl, '# Each day must have at least one animal protein'), 300)
        ]);

        return $hlpl;
    }
}
