<?php

namespace App\Console\Commands;

use Elytica\ComputeClient\ComputeService;
use Illuminate\Console\Command;

class ListElyticaApplications extends Command
{
    protected $signature = 'elytica:list-apps';
    protected $description = 'List available Elytica applications';

    public function handle()
    {
        try {
            $token = config('services.elytica.token') ?? env('ELYTICA_TOKEN');
            $client = new ComputeService($token);

            $apps = $client->getApplications();

            $this->info('Available Elytica Applications:');
            $this->line('');
            $this->line('Raw data: ' . json_encode($apps, JSON_PRETTY_PRINT));

            if (is_array($apps) || is_object($apps)) {
                foreach ($apps as $app) {
                    $this->line('App: ' . json_encode($app));
                }
            }

        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            return 1;
        }
    }
}
