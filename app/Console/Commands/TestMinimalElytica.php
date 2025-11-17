<?php

namespace App\Console\Commands;

use App\Services\ElyticaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestMinimalElytica extends Command
{
    protected $signature = 'elytica:test-minimal';
    protected $description = 'Test Elytica with a minimal HLPL model';

    public function handle()
    {
        $this->info('Testing Elytica with minimal model...');

        try {
            $service = new ElyticaService();

            // Create a simple test job
            $jobName = 'minimal_test_' . time();
            $modelData = [
                'weight' => 70,
                'height' => 170,
                'age' => 25,
                'activity_factor' => 1.5,
                'goal' => 0,
                'gender' => 1,
            ];

            $this->info('Creating job: ' . $jobName);
            $result = $service->createJob($jobName, $modelData);

            $this->info('Job created successfully!');
            $this->line('Job ID: ' . $result['job_id']);
            $this->line('Job Name: ' . $result['job_name']);

            // Poll for completion
            $this->info('Waiting for job to complete...');
            $maxAttempts = 30;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);
                $status = $service->getJobStatus($result['job_id']);

                $this->line("Attempt $attempt: Status = " . $status['status']);

                if ($status['status'] === 'completed') {
                    $this->info('✅ Job completed successfully!');
                    $this->line('Results: ' . json_encode($status['data'], JSON_PRETTY_PRINT));
                    return 0;
                } elseif ($status['status'] === 'failed') {
                    $this->error('❌ Job failed!');
                    $this->error('Error: ' . ($status['error'] ?? 'Unknown error'));
                    return 1;
                }

                $attempt++;
            }

            $this->warn('⏱️  Job timed out after ' . ($maxAttempts * 2) . ' seconds');
            return 1;

        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
