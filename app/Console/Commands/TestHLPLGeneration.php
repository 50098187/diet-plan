<?php

namespace App\Console\Commands;

use App\Services\ElyticaService;
use Illuminate\Console\Command;

class TestHLPLGeneration extends Command
{
    protected $signature = 'hlpl:test';
    protected $description = 'Generate and display HLPL model for debugging';

    public function handle()
    {
        $this->info('Generating HLPL model...');

        // Create test data
        $modelData = [
            'weight' => 75,
            'height' => 175,
            'age' => 30,
            'activity_factor' => 1.55,
            'goal' => 0, // maintenance
            'gender' => 1, // male
        ];

        // Use reflection to access protected method
        $service = new ElyticaService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateHLPLModel');
        $method->setAccessible(true);

        try {
            $hlpl = $method->invoke($service, $modelData);

            // Save to file
            $outputPath = storage_path('app/test_model.hlpl');
            file_put_contents($outputPath, $hlpl);

            $this->info('HLPL model saved to: ' . $outputPath);
            $this->info('Model length: ' . strlen($hlpl) . ' characters');

            // Show first 2000 characters
            $this->line("\n=== HLPL Model Preview (first 2000 chars) ===");
            $this->line(substr($hlpl, 0, 2000));

            // Show last 500 characters to check if it's complete
            $this->line("\n=== HLPL Model End (last 500 chars) ===");
            $this->line(substr($hlpl, -500));

        } catch (\Exception $e) {
            $this->error('Failed to generate HLPL model: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
