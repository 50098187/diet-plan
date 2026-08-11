<?php

namespace App\Services;

use App\Models\Food;
use Elytica\ComputeClient\ComputeService;
use Illuminate\Support\Facades\Log;

class ElyticaService
{
    protected $client;
    protected $modelPath;
    protected $projectName = 'namc-diet-plan';
    protected $projectId = null;
    protected $applicationId;

    public function __construct()
    {
        $token = config('services.elytica.token') ?? env('ELYTICA_TOKEN');

        if (!$token) {
            throw new \Exception('Elytica token not configured. Please set ELYTICA_TOKEN in your .env file.');
        }

        $this->applicationId = config('services.elytica.application_id') ?? env('ELYTICA_APPLICATION_ID', 14);
        $this->client = new ComputeService($token);
        $this->modelPath = base_path(env('ELYTICA_MODEL_PATH', 'app/Services/model.hlpl'));

        // Try to ensure project exists
        try {
            $this->ensureProjectExists();
        } catch (\Exception $e) {
            Log::warning('Could not ensure project exists during initialisation', [
                'error' => $e->getMessage()
            ]);
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

            // Step 1: Create the job
            $jobResponse = $this->client->createNewJob($this->projectId, $jobName);
            $jobId = $jobResponse->id ?? null;

            if (!$jobId) {
                throw new \Exception('Failed to create job');
            }

            Log::info('Created job', ['job_id' => $jobId, 'job_name' => $jobName]);

            // Step 2: Generate JSON data file
            $jsonData = $this->generateUserDataJSON($modelData);
            $jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // Save JSON for debugging
            $debugJsonPath = storage_path('app/debug_data_' . $jobId . '.json');
            file_put_contents($debugJsonPath, $jsonContent);

            Log::info('Generated user data JSON', [
                'json_size' => strlen($jsonContent),
                'debug_path' => $debugJsonPath
            ]);

            // Step 3: Read model.hlpl
            $modelContent = file_get_contents($this->modelPath);
            Log::info('Read model.hlpl file', ['model_path' => $this->modelPath]);

            // Step 4: Upload model.hlpl
            $modelFileResponse = $this->client->uploadInputFile(
                $jobId . '.hlpl',
                $modelContent,
                $this->projectId
            );

            $modelFileId = $this->extractFileId($modelFileResponse);
            if (!$modelFileId) {
                throw new \Exception('Failed to upload model file');
            }

            Log::info('Uploaded model file', ['file_id' => $modelFileId]);

            // Step 5: Upload data.json
            $dataFileResponse = $this->client->uploadInputFile(
                'data.json',
                $jsonContent,
                $this->projectId
            );

            $dataFileId = $this->extractFileId($dataFileResponse);
            if (!$dataFileId) {
                throw new \Exception('Failed to upload data file');
            }

            Log::info('Uploaded data file', ['file_id' => $dataFileId]);

            // Step 6-7: Assign files to job
            $this->client->assignFileToJob($this->projectId, $jobId, $modelFileId, 1);
            $this->client->assignFileToJob($this->projectId, $jobId, $dataFileId, 2);

            Log::info('Assigned files to job');

            // Step 8: Queue the job
            $this->client->queueJob($jobId);
            Log::info('Queued job for execution', ['job_id' => $jobId]);

            return [
                'job_id' => $jobId,
                'job_name' => $jobName
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Elytica job', [
                'error' => $e->getMessage(),
                'job_name' => $jobName
            ]);
            throw $e;
        }
    }

    /**
     * Extract file ID from upload response
     */
    protected function extractFileId($response): ?int
    {
        if (isset($response->newfiles) && is_array($response->newfiles) && count($response->newfiles) > 0) {
            return $response->newfiles[0]->id ?? null;
        }
        return $response->id ?? null;
    }

    /**
     * Get the status and result of a job
     */
    public function getJobStatus(int $jobId): array
    {
        try {
            if (!$this->projectId) {
                throw new \Exception('Project ID not found');
            }

            $jobs = $this->client->getJobs($this->projectId);

            if (!$jobs || !is_iterable($jobs)) {
                $results = $this->getJobResults($jobId);
                if ($results) {
                    return ['status' => 'completed', 'data' => $results];
                }
                return ['status' => 'error', 'data' => null, 'error' => 'Failed to retrieve job status'];
            }

            $currentJob = null;
            foreach ($jobs as $job) {
                if ($job->id == $jobId) {
                    $currentJob = $job;
                    break;
                }
            }

            if (!$currentJob) {
                return ['status' => 'not_found', 'data' => null];
            }

            // Status mapping: 0=RESET, 1=QUEUED, 2=ACCEPT, 3=PROCESS, 4=COMPLETED, 5=HALTED
            $statusMap = [
                0 => 'pending',
                1 => 'queued',
                2 => 'running',
                3 => 'running',
                4 => 'completed',
                5 => 'failed'
            ];

            $status = $statusMap[$currentJob->status] ?? 'unknown';
            Log::info('Job status checked', ['job_id' => $jobId, 'status' => $status]);

            if ($status === 'failed') {
                return ['status' => 'failed', 'data' => null, 'error' => $currentJob->failure_reason ?? 'No failure reason provided'];
            }

            if ($status === 'completed') {
                $results = $this->getJobResults($jobId);
                return ['status' => 'completed', 'data' => $results];
            }

            return ['status' => $status, 'data' => null];
        } catch (\Exception $e) {
            Log::error('Failed to get job status', ['error' => $e->getMessage(), 'job_id' => $jobId]);
            throw $e;
        }
    }

    /**
     * Get the results of a completed job
     */
    protected function getJobResults(int $jobId): ?array
    {
        try {
            if (!$this->projectId) {
                $this->ensureProjectExists();
                if (!$this->projectId) {
                    return null;
                }
            }

            $outputFiles = $this->client->getOutputFiles($jobId, $this->projectId);
            $outputFilesArray = is_iterable($outputFiles) ? iterator_to_array($outputFiles) : [];

            if (empty($outputFilesArray)) {
                return null;
            }

            // Find JSON results file
            foreach ($outputFilesArray as $file) {
                if ($file->filename === 'results' || strpos($file->filename, '.json') !== false) {
                    $tempFile = tempnam(sys_get_temp_dir(), 'elytica_');
                    $this->client->downloadFile($this->projectId, $file->id, $tempFile);
                    $contents = file_get_contents($tempFile);
                    unlink($tempFile);

                    $results = json_decode($contents, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $results;
                    }
                }
            }

            // Try extracting JSON from output logs
            foreach ($outputFilesArray as $file) {
                $tempFile = tempnam(sys_get_temp_dir(), 'elytica_output_');
                $this->client->downloadFile($this->projectId, $file->id, $tempFile);
                $contents = file_get_contents($tempFile);
                unlink($tempFile);

                if (preg_match('/===\s*WRITING RESULTS\s*===\s*\n([\s\S]+?)\n\s*✓ Results written successfully/i', $contents, $matches)) {
                    $jsonStr = trim($matches[1]);
                    $results = json_decode($jsonStr, true);
                    if ($results) {
                        return $results;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get job results', ['error' => $e->getMessage(), 'job_id' => $jobId]);
            return null;
        }
    }

    /**
     * Ensure the project exists on Elytica
     */
    protected function ensureProjectExists(): void
    {
        $envProjectId = (int) env('ELYTICA_PROJECT_ID', -1);
        if ($envProjectId > 0) {
            $this->projectId = $envProjectId;
            Log::info('Using project ID from environment', ['project_id' => $this->projectId]);
            return;
        }

        try {
            $projects = $this->client->getProjects();

            if ($projects && is_iterable($projects)) {
                foreach ($projects as $project) {
                    if (isset($project->name) && $project->name === $this->projectName) {
                        $this->projectId = (int) $project->id;
                        Log::info('Found existing project', ['project_id' => $this->projectId]);
                        return;
                    }
                }

                // Create new project
                $response = $this->client->createNewProject(
                    $this->projectName,
                    'NAMC Diet Optimisation Project',
                    $this->applicationId,
                    null,
                    null
                );

                if ($response && isset($response->id)) {
                    $this->projectId = (int) $response->id;
                    Log::info('Created new project', ['project_id' => $this->projectId]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in ensureProjectExists', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate JSON data file for HLPL model
     * Implements monthly optimisation as per Formulation 4
     *
     * @param array $userData
     * @return array
     */
    protected function generateUserDataJSON(array $userData = []): array
    {
        // Load all foods from database (NAMC food basket)
        $allFoods = Food::all();

        if ($allFoods->isEmpty()) {
            throw new \Exception('No food data found in database. Please run: php artisan db:seed --class=FoodSeeder');
        }

        // Filter by diet type
        $dietType = $userData['diet_type'] ?? 'normal';
        $foods = $allFoods;

        if ($dietType === 'vegan') {
            $foods = $allFoods->filter(function ($food) {
                $name = strtolower($food->product);
                $animalProducts = ['egg', 'milk', 'cheese', 'beef', 'fish', 'chicken', 'polony', 'offal', 'giblet'];
                foreach ($animalProducts as $product) {
                    if (strpos($name, $product) !== false) {
                        return false;
                    }
                }
                return true;
            });
        } elseif ($dietType === 'vegetarian') {
            $foods = $allFoods->filter(function ($food) {
                $name = strtolower($food->product);
                $meatProducts = ['beef', 'fish', 'chicken', 'polony', 'offal', 'giblet'];
                foreach ($meatProducts as $product) {
                    if (strpos($name, $product) !== false) {
                        return false;
                    }
                }
                return true;
            });
        }

        if ($foods->isEmpty()) {
            throw new \Exception('No suitable foods available for your diet preference.');
        }

        Log::info('Generating HLPL model', [
            'diet_type' => $dietType,
            'food_count' => $foods->count()
        ]);

        // Extract user data
        $weight = $userData['weight'] ?? 70;
        $height = $userData['height'] ?? 175;
        $age = $userData['age'] ?? 35;
        $gender = $userData['gender'] ?? 'male';
        $activityFactor = $userData['activity_factor'] ?? 1.55;
        $goal = $userData['goal'] ?? 0;

        // Calculate metabolic values (daily)
        $genderBinary = ($gender === 'male' || $gender === 1) ? 1 : 0;
        $bmrMale = 66.5 + 13.8 * $weight + 5.0 * $height - 6.8 * $age;
        $bmrFemale = 655.1 + 9.6 * $weight + 1.9 * $height - 4.7 * $age;
        $bmr = $genderBinary * $bmrMale + (1 - $genderBinary) * $bmrFemale;

        $decade = floor($age / 10) - 1;
        $ree = 1 - 0.05 * $decade;
        $bmr2 = $bmr * $ree;
        $tdee = $bmr2 * $activityFactor;

        // Calculate MONTHLY targets (30 days)
        $daysPerMonth = 30;
        $dailyEnergyKcal = $tdee + ($goal * 500);
        $monthlyEnergyKcal = $dailyEnergyKcal * $daysPerMonth;
        $monthlyEnergyKJ = $monthlyEnergyKcal * 4.184;

        // Macronutrient targets (monthly)
        // Protein: 1.6g per kg body weight per day (for active individuals)
        $dailyProtein = 1.6 * $weight;
        $monthlyProtein = $dailyProtein * $daysPerMonth;

        // Carbs: 45-65% of total calories (use 50% as target)
        $dailyCarbs = ($dailyEnergyKcal * 0.50) / 4; // 4 kcal per gram
        $monthlyCarbs = $dailyCarbs * $daysPerMonth;

        // Fat: 20-35% of total calories (use 30% as target)
        $dailyFat = ($dailyEnergyKcal * 0.30) / 9; // 9 kcal per gram
        $monthlyFat = $dailyFat * $daysPerMonth;

        // Build foods array
        $foodsArray = [];
        foreach ($foods as $food) {
            $foodsArray[] = [
                'name' => $food->product,
                'category' => $food->category,
                'cost' => (float) $food->price_per_unit,
                'protein' => (float) $food->protein,
                'carbs' => (float) $food->carbs,
                'fat' => (float) $food->fat,
                'energy_kj' => (float) $food->energy_kj,
                'calories' => round($food->energy_kj / 4.184, 2)
            ];
        }

        // Constraints (as per thesis)
        $constraints = [
            'budget_max' => 2619, // R2,619/month (50% of SA minimum wage)
            'min_variety' => 5,   // Minimum 5 different foods
            'max_servings' => 5,  // Maximum 5 servings per food item
            'target_energy' => round($monthlyEnergyKJ, 2),
            'target_protein' => round($monthlyProtein, 2),
            'target_carbs' => round($monthlyCarbs, 2),
            'target_fat' => round($monthlyFat, 2)
        ];

        // Weights for goal programming (ωC ≫ ωP, ωK, ωA)
        $weights = [
            'cost' => 1000,     // ωC - heavily weighted
            'protein' => 1,     // ωP
            'carbs' => 1,       // ωK
            'fat' => 1          // ωA
        ];

        // Build JSON structure
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
                'bmr' => round($bmr, 2),
                'bmr2' => round($bmr2, 2),
                'tdee' => round($tdee, 2),
                'daily_energy_kcal' => round($dailyEnergyKcal, 2),
                'monthly_energy_kj' => round($monthlyEnergyKJ, 2)
            ],
            'constraints' => $constraints,
            'weights' => $weights
        ];

        Log::info('Generated JSON data for monthly optimisation', [
            'food_count' => count($foodsArray),
            'budget' => $constraints['budget_max'],
            'monthly_energy_kj' => $constraints['target_energy']
        ]);

        return $jsonData;
    }
}
