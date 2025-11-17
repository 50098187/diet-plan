<?php

namespace App\Console\Commands;

use App\Models\Food;
use App\Services\WoolworthsApiService;
use App\Services\WoolworthsScraperService;
use App\Services\CheckersScraperService;
use App\Services\DuskCheckersScraper;
use Illuminate\Console\Command;

class UpdateFoodPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foods:update-prices
                            {--source=all : Update prices for specific source (all, woolworths, checkers, manual)}
                            {--method=scraper : Method to use (api, scraper, dusk)}
                            {--force : Force update, skip cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update food prices from external sources (API or web scraping)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $source = $this->option('source');
        $method = $this->option('method');
        $force = $this->option('force');

        $this->info('Starting food price update...');
        $this->info("Method: {$method}");
        $this->newLine();

        if ($source === 'woolworths' || $source === 'all') {
            if ($method === 'scraper') {
                $this->updateWoolworthsPricesScraper($force);
            } else {
                $this->updateWoolworthsPrices();
            }
        }

        if ($source === 'checkers' || $source === 'all') {
            if ($method === 'dusk') {
                $this->updateCheckersPricesDusk($force);
            } elseif ($method === 'scraper') {
                $this->updateCheckersPricesScraper($force);
            } else {
                $this->warn('⚠ Checkers API not available - using Dusk browser automation');
                $this->updateCheckersPricesDusk($force);
            }
        }

        if ($source === 'manual' || $source === 'all') {
            $this->info('Manual prices do not need automatic updates.');
        }

        $this->newLine();
        $this->info('Price update completed!');
    }

    /**
     * Update prices from Woolworths API
     */
    protected function updateWoolworthsPrices()
    {
        $this->line('Updating Woolworths prices via API...');

        // Check if API service is available
        if (!config('services.woolworths.api_key')) {
            $this->warn('⚠ Woolworths API key not configured. Skipping...');
            $this->comment('Add WOOLWORTHS_API_KEY to your .env file to enable this feature.');
            $this->comment('Or use --method=scraper to scrape prices instead.');
            return;
        }

        try {
            $apiService = app(WoolworthsApiService::class);
            $updatedCount = $apiService->updateFoodPrices();

            $this->info("✓ Updated {$updatedCount} Woolworths products");
        } catch (\Exception $e) {
            $this->error('✗ Failed to update Woolworths prices: ' . $e->getMessage());
        }
    }

    /**
     * Update prices from Woolworths by web scraping
     *
     * ⚠️ WARNING: This may violate Woolworths Terms of Service
     */
    protected function updateWoolworthsPricesScraper($force = false)
    {
        $this->warn('⚠ Using web scraper - this may violate Woolworths ToS');
        $this->warn('⚠ You are responsible for any legal consequences');
        $this->newLine();

        if (!$this->confirm('Do you accept responsibility and want to continue?', false)) {
            $this->info('Cancelled.');
            return;
        }

        $this->line('Scraping Woolworths prices...');
        $this->comment('Rate limited: 2 seconds between requests');
        $this->newLine();

        try {
            $scraper = app(WoolworthsScraperService::class);
            $stats = $scraper->updateAllFoodPrices($force);

            $this->newLine();
            $this->info("✓ Updated: {$stats['updated']} foods");
            $this->comment("⊘ Skipped: {$stats['skipped']} foods (price unchanged)");
            $this->error("✗ Failed: {$stats['failed']} foods");

        } catch (\Exception $e) {
            $this->error('✗ Failed to scrape Woolworths prices: ' . $e->getMessage());
        }
    }

    /**
     * Update prices from Checkers by web scraping
     *
     * ⚠️ WARNING: This may violate Checkers Terms of Service
     */
    protected function updateCheckersPricesScraper($force = false)
    {
        $this->warn('⚠ Using web scraper - this may violate Checkers ToS');
        $this->warn('⚠ Checkers uses anti-bot protection - expect failures');
        $this->warn('⚠ You are responsible for any legal consequences');
        $this->newLine();

        if (!$this->confirm('Do you accept responsibility and want to continue?', false)) {
            $this->info('Cancelled.');
            return;
        }

        $this->line('Scraping Checkers prices...');
        $this->comment('Rate limited: 2 seconds between requests');
        $this->newLine();

        try {
            $scraper = app(CheckersScraperService::class);
            $stats = $scraper->updateAllFoodPrices($force);

            $this->newLine();
            $this->info("✓ Updated: {$stats['updated']} foods");
            $this->comment("⊘ Skipped: {$stats['skipped']} foods (price unchanged)");
            $this->error("✗ Failed: {$stats['failed']} foods");

            if (isset($stats['blocked'])) {
                $this->warn("⊗ Blocked: {$stats['blocked']} requests (anti-bot)");
            }

        } catch (\Exception $e) {
            $this->error('✗ Failed to scrape Checkers prices: ' . $e->getMessage());
        }
    }

    /**
     * Update prices from Checkers using browser automation (Dusk)
     *
     * ⚠️ WARNING: This may violate Checkers Terms of Service
     * ✅ ADVANTAGE: Bypasses anti-bot protection (403 errors)
     */
    protected function updateCheckersPricesDusk($force = false)
    {
        $this->info('🌐 Using browser automation (Dusk) for Checkers');
        $this->warn('⚠ This may violate Checkers ToS');
        $this->warn('⚠ You are responsible for any legal consequences');
        $this->newLine();

        if (!$this->confirm('Do you accept responsibility and want to continue?', false)) {
            $this->info('Cancelled.');
            return;
        }

        $this->line('Starting headless browser automation...');
        $this->comment('ChromeDriver will be used to bypass anti-bot protection');
        $this->comment('Rate limited: 3 seconds between requests');
        $this->newLine();

        try {
            $scraper = app(DuskCheckersScraper::class);
            $stats = $scraper->updateAllFoodPrices($force);

            $this->newLine();
            $this->info("✓ Updated: {$stats['updated']} foods");
            $this->comment("⊘ Skipped: {$stats['skipped']} foods (price unchanged)");
            $this->error("✗ Failed: {$stats['failed']} foods");

        } catch (\Exception $e) {
            $this->error('✗ Failed to run browser automation: ' . $e->getMessage());
            $this->newLine();
            $this->comment('Make sure ChromeDriver is installed:');
            $this->line('  php artisan dusk:chrome-driver');
        }
    }
}
