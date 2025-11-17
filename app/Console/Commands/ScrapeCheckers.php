<?php

namespace App\Console\Commands;

use App\Services\CheckersScraperService;
use App\Models\Food;
use Illuminate\Console\Command;

class ScrapeCheckers extends Command
{
    protected $signature = 'scrape:checkers';
    protected $description = 'Scrape Checkers for food prices and update database';

    public function handle()
    {
        $this->info('Starting Checkers scraper...');

        $scraper = app(CheckersScraperService::class);

        // List of common foods to scrape
        $productsToScrape = [
            'chicken breast',
            'brown rice',
            'eggs',
            'salmon',
            'oats',
            'beef',
            'greek yogurt',
            'bread',
            'lentils',
            'broccoli',
            'spinach',
            'sweet potato',
            'banana',
            'almonds',
        ];

        $successCount = 0;
        $failCount = 0;

        foreach ($productsToScrape as $productName) {
            $this->info("Searching for: {$productName}");

            try {
                $result = $scraper->searchProduct($productName);

                if ($result && !empty($result['products'])) {
                    // Take the first/best match
                    $product = $result['products'][0];

                    Food::updateOrCreate(
                        [
                            'name' => $product['name'],
                            'source' => 'checkers'
                        ],
                        [
                            'serving_size' => $product['serving_size'] ?? '100g',
                            'protein' => $product['protein'] ?? 0,
                            'carbs' => $product['carbs'] ?? 0,
                            'fat' => $product['fat'] ?? 0,
                            'fiber' => $product['fiber'] ?? 0,
                            'energy_kj' => $product['energy_kj'] ?? 0,
                            'calories' => $product['calories'] ?? round(($product['energy_kj'] ?? 0) / 4.184, 2),
                            'cost' => $product['price'],
                            'price_updated_at' => now(),
                            'is_active' => true,
                        ]
                    );

                    $this->info("✓ Saved: {$product['name']} - R{$product['price']}");
                    $successCount++;
                } else {
                    $this->warn("✗ No results found for: {$productName}");
                    $failCount++;
                }

                // Be nice to the server
                sleep(2);

            } catch (\Exception $e) {
                $this->error("✗ Error scraping {$productName}: " . $e->getMessage());
                $failCount++;
            }
        }

        $this->info("\nScraping complete!");
        $this->info("Success: {$successCount} | Failed: {$failCount}");

        return 0;
    }
}
