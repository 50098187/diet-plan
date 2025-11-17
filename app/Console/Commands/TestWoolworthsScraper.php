<?php

namespace App\Console\Commands;

use App\Services\WoolworthsScraperService;
use Illuminate\Console\Command;

class TestWoolworthsScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woolworths:test {product=eggs : Product name to search for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Woolworths scraper with a single product search';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productName = $this->argument('product');

        $this->warn('⚠ This is a web scraper test - use responsibly');
        $this->newLine();

        $this->info("Testing Woolworths scraper...");
        $this->line("Product: {$productName}");
        $this->line(str_repeat('-', 60));
        $this->newLine();

        $scraper = app(WoolworthsScraperService::class);

        $this->comment('Fetching from Woolworths.co.za...');
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
            $this->line('  - Product not found on Woolworths.co.za');
            $this->line('  - Website structure changed');
            $this->line('  - Network/timeout issues');
            $this->line('  - IP blocked by Woolworths');
            $this->newLine();
            $this->comment('Check logs: storage/logs/laravel.log');
        }

        $this->newLine();
        $this->line(str_repeat('-', 60));

        return $result ? Command::SUCCESS : Command::FAILURE;
    }
}
