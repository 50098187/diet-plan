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

            Log::info('Output files response', [
                'job_id' => $jobId,
                'type' => gettype($outputFiles),
                'is_iterable' => is_iterable($outputFiles),
                'count' => (is_countable($outputFiles) ? count($outputFiles) : 'N/A'),
                'files' => is_iterable($outputFiles) ? array_map(fn($f) => $f->filename ?? 'unknown', iterator_to_array($outputFiles)) : 'N/A'
            ]);

            if (!$outputFiles || !is_iterable($outputFiles) || (is_countable($outputFiles) && count($outputFiles) === 0)) {
                Log::warning('No output files found', ['job_id' => $jobId, 'type' => gettype($outputFiles)]);
                return null;
            }

            // Find the results JSON file (prioritize "results" file, then .json files, then output files)
            foreach ($outputFiles as $file) {
                if ($file->filename === 'results' ||
                    strpos($file->filename, '.json') !== false ||
                    strpos($file->filename, 'output') !== false) {

                    // Download the file
                    $tempFile = tempnam(sys_get_temp_dir(), 'elytica_');
                    $this->client->downloadFile($this->projectId, $file->id, $tempFile);

                    $contents = file_get_contents($tempFile);
                    unlink($tempFile);

                    Log::info('Downloaded output file', [
                        'job_id' => $jobId,
                        'file_id' => $file->id,
                        'filename' => $file->filename,
                        'content_length' => strlen($contents),
                        'content_preview' => substr($contents, 0, 500)
                    ]);

                    $results = json_decode($contents, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('JSON decode failed', [
                            'job_id' => $jobId,
                            'file_id' => $file->id,
                            'filename' => $file->filename,
                            'error' => json_last_error_msg()
                        ]);
                        continue; // Try next file
                    }

                    Log::info('Job results retrieved', [
                        'job_id' => $jobId,
                        'file_id' => $file->id,
                        'results' => $results
                    ]);

                    return $results;
                }
            }

            // Try to extract JSON from stdout/output logs
            Log::info('No JSON file found, checking output logs', ['job_id' => $jobId]);

            if ($outputFiles && is_iterable($outputFiles)) {
                foreach ($outputFiles as $file) {
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

                    // Look for JSON in the output (between === WRITING RESULTS === and end)
                    if (preg_match('/===\s*WRITING RESULTS\s*===\s*\n(.+?)\n.*Results written successfully/s', $contents, $matches)) {
                        $jsonStr = trim($matches[1]);
                        $results = json_decode($jsonStr, true);

                        if ($results) {
                            Log::info('Job results extracted from output logs', [
                                'job_id' => $jobId,
                                'file' => $file->filename
                            ]);
                            return $results;
                        }
                    }
                }
            }

            Log::warning('No JSON results found in any output files', [
                'job_id' => $jobId,
                'files' => $outputFiles && is_iterable($outputFiles) ? array_map(fn($f) => $f->filename, $outputFiles) : []
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
     * Generate HLPL model content dynamically from database
     *
     * @param array $userData User input data (weight, height, age, gender, activity_factor, goal)
     * @return string
     */
    protected function generateHLPLModel(array $userData = []): string
    {
        // ONLY use foods from actual scrapers (woolworths, checkers, crowd-sourced)
        // DO NOT use placeholder/manual data with R5 prices
        $foods = Food::active()
            ->whereIn('source', ['woolworths', 'checkers', 'crowd-sourced'])
            ->get();

        $count = $foods->count();

        // Ensure we have real food data
        if ($count === 0) {
            Log::warning('No store food data found in database');
            throw new \Exception('No store pricing data available. The system uses real prices from Woolworths and Checkers only. Please contact the administrator to enable price updates.');
        }

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
        }

        // Calculate metabolic values
        $decade = floor($age / 10) - 1;
        $genderBinary = ($gender === 'male' || $gender === 1) ? 1 : 0;

        $bmrMale = 66.5 + 13.8 * $weight + 5.0 * $height - 6.8 * $age;
        $bmrFemale = 655.1 + 9.6 * $weight + 1.9 * $height - 4.7 * $age;
        $ree = 1 - 0.05 * $decade;

        $bmr = $genderBinary * $bmrMale + (1 - $genderBinary) * $bmrFemale;
        $bmr2 = $bmr * $ree;
        $tdee = $bmr2 * $activityFactor;
        $targetCalories = $tdee + ($goal * 500);

        // Format arrays for HLPL
        $costsStr = '{' . implode(', ', $costs) . '}';
        $proteinsStr = '{' . implode(', ', $proteins) . '}';
        $carbsStr = '{' . implode(', ', $carbs) . '}';
        $fatsStr = '{' . implode(', ', $fats) . '}';
        $energiesStr = '{' . implode(', ', $energies) . '}';
        $fibersStr = '{' . implode(', ', $fibers) . '}';

        // JSON arrays for Python
        $foodNamesJson = json_encode($foodNames);
        $servingSizesJson = json_encode($servingSizes);
        $costsJson = json_encode($costs);
        $proteinsJson = json_encode($proteins);
        $carbsJson = json_encode($carbs);
        $fatsJson = json_encode($fats);
        $energiesJson = json_encode($energies);
        $fibersJson = json_encode($fibers);
        $sourcesJson = json_encode($sources);

        // Nutritional requirements
        $proteinMin = 100;
        $carbsMin = 150;
        $carbsMax = 400;
        $fatMin = 30;
        $fatMax = 100;
        $fiberMin = 20;

        $genderStr = ($genderBinary == 1) ? 'Male' : 'Female';
        $goalStr = ($goal == -1) ? 'Weight Loss' : (($goal == 1) ? 'Weight Gain' : 'Maintenance');

        // Generate HLPL model
        $hlpl = <<<HLPL
model diet_plan
set FOODS = {$indicesStr};

const cost = {$costsStr}, forall f in FOODS;
const protein = {$proteinsStr}, forall f in FOODS;
const carbs = {$carbsStr}, forall f in FOODS;
const fat = {$fatsStr}, forall f in FOODS;
const calories = {$energiesStr}, forall f in FOODS;
const fiber = {$fibersStr}, forall f in FOODS;

var 0<=servings<=3, forall f in FOODS;
bin isused, forall f in FOODS;

min sum_{f in FOODS}{cost_{f} * servings_{f}};

constr sum_{f in FOODS}{calories_{f} * servings_{f}} >= {$targetCalories};
constr sum_{f in FOODS}{protein_{f} * servings_{f}} >= {$proteinMin};
constr sum_{f in FOODS}{carbs_{f} * servings_{f}} >= {$carbsMin};
constr sum_{f in FOODS}{carbs_{f} * servings_{f}} <= {$carbsMax};
constr sum_{f in FOODS}{fat_{f} * servings_{f}} >= {$fatMin};
constr sum_{f in FOODS}{fat_{f} * servings_{f}} <= {$fatMax};
constr sum_{f in FOODS}{fiber_{f} * servings_{f}} >= {$fiberMin};

constr sum_{f in FOODS}{isused_{f}} >= 5;
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

    print("=== DIET OPTIMIZATION MODEL ===")
    print("Weight: {$weight}kg, Height: {$height}cm, Age: {$age}")
    print("Gender: {$genderStr}")
    print("Activity Factor: {$activityFactor}")
    print("Goal: {$goalStr}")

    print("\\nSetting solver parameters...")
    elytica.set_gap_limit("diet_plan", 0.001)
    elytica.set_time_limit("diet_plan", 60)

    print("Initializing optimization model...")
    elytica.init_model("diet_plan")

    print("Solving optimization problem...")
    elytica.run_model("diet_plan")

    optimal_cost = elytica.get_best_primal_bound("diet_plan")

    results = {
        "optimal_cost": round(optimal_cost, 2),
        "bmr": round(bmr, 2),
        "bmr_adjusted": round(bmr2, 2),
        "tdee": round(tdee, 2),
        "target_calories": round(target_cals, 2),
        "foods": [],
        "totals": {
            "calories": 0,
            "protein": 0,
            "carbs": 0,
            "fat": 0,
            "fiber": 0
        }
    }

    print("\\n=== METABOLIC CALCULATIONS ===")
    print("BMR: " + str(round(bmr)) + " kcal")
    print("BMR Adjusted: " + str(round(bmr2)) + " kcal")
    print("TDEE: " + str(round(tdee)) + " kcal")
    print("Target Calories: " + str(round(target_cals)) + " kcal")

    print("\\n=== OPTIMAL DIET PLAN ===")
    print("Minimum Cost: R" + str(round(optimal_cost, 2)))

    for i in range(1, {$count} + 1):
        var_name = "servings" + str(i)
        servings = elytica.get_variable_value("diet_plan", var_name)

        if servings > 0.01:
            food_index = i - 1
            food_info = {
                "name": food_names[food_index],
                "servings": round(servings, 2),
                "serving_size": serving_sizes[food_index],
                "cost": round(servings * costs_list[food_index], 2),
                "source": sources_list[food_index]
            }
            results["foods"].append(food_info)

            results["totals"]["protein"] += servings * proteins_list[food_index]
            results["totals"]["carbs"] += servings * carbs_list[food_index]
            results["totals"]["fat"] += servings * fats_list[food_index]
            results["totals"]["calories"] += servings * calories_list[food_index]
            results["totals"]["fiber"] += servings * fiber_list[food_index]

            print(food_names[food_index] + ": " + str(round(servings, 2)) + " servings (R" + str(round(servings * costs_list[food_index], 2)) + ")")

    for key in results["totals"]:
        results["totals"][key] = round(results["totals"][key], 2)

    print("\\n=== NUTRITIONAL TOTALS ===")
    print("Calories: " + str(round(results['totals']['calories'])) + " kcal")
    print("Protein: " + str(round(results['totals']['protein'], 1)) + "g")
    print("Carbs: " + str(round(results['totals']['carbs'], 1)) + "g")
    print("Fat: " + str(round(results['totals']['fat'], 1)) + "g")
    print("Fiber: " + str(round(results['totals']['fiber'], 1)) + "g")

    results_json = json.dumps(results, indent=2)
    print("\\n=== WRITING RESULTS ===")
    print(results_json)

    elytica.write_results(results_json)
    print("Results written successfully")

    return 0

HLPL;

        return $hlpl;
    }
}
