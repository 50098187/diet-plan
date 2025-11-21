<?php

namespace App\Console\Commands;

use App\Models\Food;
use App\Services\WoolworthsScraperService;
use App\Services\CheckersScraperService;
use App\Services\DuskCheckersScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateFoodPackages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foods:update-packages
                            {--source=all : Update packages for specific source (all, woolworths, checkers)}
                            {--method=scraper : Method to use (scraper, dusk)}
                            {--force : Force update, skip cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search for multiple package sizes for each food and store them';

    /**
     * Common package size variations to search for (in grams)
     */
    protected $commonPackageSizes = [
        'dry_goods' => ['100g', '200g', '250g', '400g', '500g', '750g', '1kg', '2kg'],
        'meat' => ['150g', '200g', '250g', '300g', '400g', '500g', '1kg'],
        'produce' => ['100g', '250g', '500g', '1kg'],
        'dairy' => ['150g', '200g', '250g', '500g', '1kg'],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Increase memory limit for this command
        ini_set('memory_limit', '512M');

        $source = $this->option('source');
        $method = $this->option('method');
        $force = $this->option('force');

        $this->info('Searching for multiple package sizes for each food...');
        $this->info("Method: {$method}");
        $this->info("Memory limit: " . ini_get('memory_limit'));
        $this->newLine();

        $foods = Food::active()->get();

        $this->withProgressBar($foods, function ($food) use ($source, $method, $force) {
            $this->updateFoodPackages($food, $source, $method, $force);

            // Force garbage collection after each food to free memory
            gc_collect_cycles();
        });

        $this->newLine();
        $this->newLine();
        $this->info('Package update completed!');
    }

    /**
     * Update packages for a single food item
     */
    protected function updateFoodPackages(Food $food, string $source, string $method, bool $force)
    {
        $foodType = $this->detectFoodType($food->name);
        $packagesToSearch = $this->commonPackageSizes[$foodType];

        $foundPackages = [];

        // Search Woolworths
        if ($source === 'all' || $source === 'woolworths') {
            $woolworthsPackages = $this->searchWoolworths($food->name, $packagesToSearch, $method, $force);
            $foundPackages = array_merge($foundPackages, $woolworthsPackages);
        }

        // Search Checkers
        if ($source === 'all' || $source === 'checkers') {
            $checkersPackages = $this->searchCheckers($food->name, $packagesToSearch, $method, $force);
            $foundPackages = array_merge($foundPackages, $checkersPackages);
        }

        // Remove duplicates (same size from both stores - keep cheapest)
        $foundPackages = $this->deduplicatePackages($foundPackages);

        if (count($foundPackages) > 0) {
            $food->update([
                'packages' => $foundPackages,
                'price_updated_at' => now()
            ]);

            // Update the cost field to reflect cheapest package
            $food->updateCostFromPackages();
        }
    }

    /**
     * Search Woolworths for package sizes
     */
    protected function searchWoolworths(string $foodName, array $sizes, string $method, bool $force)
    {
        $scraper = app(WoolworthsScraperService::class);
        $packages = [];

        foreach ($sizes as $size) {
            $searchTerm = "{$foodName} {$size}";
            $result = $scraper->searchProduct($searchTerm);

            if ($result && isset($result['price'])) {
                $grams = $this->extractGrams($size);
                $packages[] = [
                    'size' => $size,
                    'price' => $result['price'],
                    'price_per_gram' => round($result['price'] / $grams, 4),
                    'source' => 'woolworths'
                ];
            }

            // Free memory
            unset($result);

            // Rate limit
            sleep(2);
        }

        return $packages;
    }

    /**
     * Search Checkers for package sizes
     */
    protected function searchCheckers(string $foodName, array $sizes, string $method, bool $force)
    {
        if ($method === 'dusk') {
            $scraper = app(DuskCheckersScraper::class);
        } else {
            $scraper = app(CheckersScraperService::class);
        }

        $packages = [];

        foreach ($sizes as $size) {
            $searchTerm = "{$foodName} {$size}";
            $result = $scraper->searchProduct($searchTerm);

            if ($result && isset($result['price'])) {
                $grams = $this->extractGrams($size);
                $packages[] = [
                    'size' => $size,
                    'price' => $result['price'],
                    'price_per_gram' => round($result['price'] / $grams, 4),
                    'source' => 'checkers'
                ];
            }

            // Free memory
            unset($result);

            // Rate limit
            sleep(3);
        }

        return $packages;
    }

    /**
     * Detect food type to determine which package sizes to search
     */
    protected function detectFoodType(string $name)
    {
        $nameLower = strtolower($name);

        $meatKeywords = ['chicken', 'beef', 'pork', 'lamb', 'salmon', 'fish', 'liver'];
        foreach ($meatKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                return 'meat';
            }
        }

        $dairyKeywords = ['yogurt', 'cheese', 'milk', 'cream'];
        foreach ($dairyKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                return 'dairy';
            }
        }

        $produceKeywords = ['pepper', 'broccoli', 'spinach', 'berries', 'avocado', 'banana', 'sweet potato'];
        foreach ($produceKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                return 'produce';
            }
        }

        return 'dry_goods'; // Default
    }

    /**
     * Extract grams from size string
     */
    protected function extractGrams(string $size)
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*kg/i', $size, $matches)) {
            return floatval($matches[1]) * 1000;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*g/i', $size, $matches)) {
            return floatval($matches[1]);
        }

        return floatval($size);
    }

    /**
     * Remove duplicate packages, keeping cheapest
     */
    protected function deduplicatePackages(array $packages)
    {
        $bySize = [];

        foreach ($packages as $pkg) {
            $size = $pkg['size'];

            if (!isset($bySize[$size]) || $pkg['price_per_gram'] < $bySize[$size]['price_per_gram']) {
                $bySize[$size] = $pkg;
            }
        }

        return array_values($bySize);
    }
}
