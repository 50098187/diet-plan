<?php

namespace App\Console\Commands;

use App\Services\CheckersScraperService;
use Illuminate\Console\Command;

class TestCheckersScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkers:test {product=eggs : Product name to search for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Checkers scraper with a single product search';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productName = $this->argument('product');

        $this->warn('⚠ This is a web scraper test - use responsibly');
        $this->warn('⚠ Checkers.co.za uses anti-bot protection - may return 403 errors');
        $this->newLine();

        $this->info("Testing Checkers scraper...");
        $this->line("Product: {$productName}");
        $this->line(str_repeat('-', 60));
        $this->newLine();

        $scraper = app(CheckersScraperService::class);

        $this->comment('Fetching from Checkers.co.za...');
        $result = $scraper->searchProduct($productName);

        $this->newLine();

        if ($result) {
            $this->info('✓ Success! Product found');
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
        } else {
            $this->error('✗ Failed to find product or extract price');
            $this->newLine();
            $this->comment('Possible reasons:');
            $this->line('  - Anti-bot protection (403 Forbidden) - most common');
            $this->line('  - Product not found on Checkers.co.za');
            $this->line('  - Website structure changed');
            $this->line('  - Network/timeout issues');
            $this->line('  - IP blocked by Checkers/Cloudflare');
            $this->newLine();
            $this->comment('Recommendations:');
            $this->line('  1. Use crowd-sourced pricing for reliable data');
            $this->line('  2. Consider browser automation (Selenium/Playwright)');
            $this->line('  3. Use rotating proxies (if legally permitted)');
            $this->newLine();
            $this->comment('Check logs: storage/logs/laravel.log');
        }

        $this->newLine();
        $this->line(str_repeat('-', 60));

        return $result ? Command::SUCCESS : Command::FAILURE;
    }
}
