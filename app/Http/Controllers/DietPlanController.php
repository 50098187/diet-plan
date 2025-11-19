<?php

namespace App\Http\Controllers;

use App\Services\ElyticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DietPlanController extends Controller
{
    protected $elyticaService;

    public function __construct(ElyticaService $elyticaService)
    {
        $this->elyticaService = $elyticaService;
    }

    /**
     * Handle the diet plan calculation request
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'weight' => 'required|numeric|min:30|max:300',
            'height' => 'required|numeric|min:100|max:250',
            'age' => 'required|numeric|min:15|max:100',
            'gender' => 'required|in:male,female',
            'activity_factor' => 'required|in:sedentary,lightly_active,moderately_active,very_active,extremely_active',
            'goal' => 'required|in:lose_fat,maintain_weight,gain_muscle',
            'diet_type' => 'required|in:normal,vegetarian,vegan',
        ]);

        // Convert activity factor to numeric multiplier
        $activityMultipliers = [
            'sedentary' => 1.2,
            'lightly_active' => 1.375,
            'moderately_active' => 1.55,
            'very_active' => 1.725,
            'extremely_active' => 1.9,
        ];

        // Convert goal to numeric value
        $goalValues = [
            'lose_fat' => -1,
            'maintain_weight' => 0,
            'gain_muscle' => 1,
        ];

        // Get or create session ID for guest users
        $sessionId = Session::getId();
        if (!$sessionId) {
            Session::start();
            $sessionId = Session::getId();
        }

        // Create job name using session ID
        $jobName = 'diet_plan_' . $sessionId . '_' . time();

        // Prepare data for the model
        $modelData = [
            'weight' => (float) $validated['weight'],
            'height' => (float) $validated['height'],
            'age' => (float) $validated['age'],
            'activity_factor' => $activityMultipliers[$validated['activity_factor']],
            'goal' => $goalValues[$validated['goal']],
            'gender' => $validated['gender'] === 'male' ? 1 : 0,
            'diet_type' => $validated['diet_type'],
        ];

        try {
            // Create project and job on Elytica
            $result = $this->elyticaService->createJob($jobName, $modelData);

            // Store job info in session
            Session::put('diet_job_id', $result['job_id']);
            Session::put('diet_job_name', $result['job_name']);

            return response()->json([
                'success' => true,
                'job_id' => $result['job_id'],
                'job_name' => $result['job_name'],
                'message' => 'Your diet plan is being calculated. Please wait...',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create calculation job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check the status of a job
     */
    public function checkStatus(Request $request)
    {
        $jobId = $request->input('job_id') ?? Session::get('diet_job_id');

        if (!$jobId) {
            return response()->json([
                'success' => false,
                'message' => 'No job ID provided',
            ], 400);
        }

        // Check if we already have cached results for this job
        $cacheKey = 'diet_job_result_' . $jobId;
        $cachedResult = Session::get($cacheKey);

        if ($cachedResult && isset($cachedResult['status']) && $cachedResult['status'] === 'completed' && isset($cachedResult['data'])) {
            return response()->json([
                'success' => true,
                'status' => $cachedResult['status'],
                'data' => $cachedResult['data'],
                'error' => null,
                'cached' => true
            ]);
        }

        try {
            $result = $this->elyticaService->getJobStatus($jobId);

            // Cache the result if it's completed or failed
            if (in_array($result['status'], ['completed', 'failed'])) {
                Session::put($cacheKey, $result);
            }

            return response()->json([
                'success' => true,
                'status' => $result['status'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check job status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
