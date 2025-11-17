<?php

namespace App\Console\Commands;

use App\Services\DuskCheckersScraper;
use Illuminate\Console\Command;

class TestDuskCheckersScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkers:test-dusk {product=eggs : Product name to search for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Checkers browser automation (Dusk) scraper with a single product search';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productName = $this->argument('product');

        $this->info('🌐 Testing Dusk-powered Checkers scraper...');
        $this->warn('⚠ This uses browser automation - use responsibly');
        $this->warn('⚠ This may violate Checkers ToS');
        $this->newLine();

        $this->line("Product: {$productName}");
        $this->line(str_repeat('-', 60));
        $this->newLine();

        $this->comment('Starting headless Chrome browser...');
        $this->comment('This will bypass anti-bot protection (403 errors)');
        $this->newLine();

        try {
            $scraper = app(DuskCheckersScraper::class);
            $result = $scraper->searchProduct($productName, false);

            $this->newLine();

            if ($result) {
                $this->info('✓ Success! Product found using browser automation');
                $this->newLine();

                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Name', $result['name']],
                        ['Price', 'R' . number_format($result['price'], 2)],
                        ['Currency', $result['currency']],
                        ['Source', $result['source']],
                    ]
                );

                $this->newLine();
                $this->info('✅ Browser automation successfully bypassed anti-bot protection!');
            } else {
                $this->error('✗ Failed to find product or extract price');
                $this->newLine();
                $this->comment('Possible reasons:');
                $this->line('  - Product not found on Checkers.co.za');
                $this->line('  - ChromeDriver not installed or outdated');
                $this->line('  - Price selectors need updating');
                $this->line('  - Network/timeout issues');
                $this->newLine();
                $this->comment('Try updating ChromeDriver:');
                $this->line('  php artisan dusk:chrome-driver');
                $this->newLine();
                $this->comment('Check logs: storage/logs/laravel.log');
            }

        } catch (\Exception $e) {
            $this->error('✗ Exception occurred');
            $this->error($e->getMessage());
            $this->newLine();
            $this->comment('Make sure Dusk and ChromeDriver are installed:');
            $this->line('  composer require --dev laravel/dusk');
            $this->line('  php artisan dusk:install');
            $this->line('  php artisan dusk:chrome-driver');
        }

        $this->newLine();
        $this->line(str_repeat('-', 60));

        return $result ? Command::SUCCESS : Command::FAILURE;
    }
}
