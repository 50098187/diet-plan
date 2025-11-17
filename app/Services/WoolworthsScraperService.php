<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Woolworths South Africa Web Scraper
 *
 * ⚠️ LEGAL WARNING:
 * This service scrapes Woolworths.co.za which may violate their Terms of Service.
 * Use at your own risk. You are responsible for any legal consequences.
 *
 * ETHICAL USAGE:
 * - Rate limited to 1 request per 2 seconds
 * - Caches results to minimize requests
 * - Respectful of server resources
 * - For personal/educational use only
 */
class WoolworthsScraperService
{
    protected $baseUrl = 'https://www.woolworths.co.za';
    protected $searchUrl = 'https://www.woolworths.co.za/cat?Ntt=';

    /**
     * Search for a product and get its price
     *
     * @param string $productName
     * @return array|null ['name', 'price', 'url']
     */
    public function searchProduct(string $productName)
    {
        // Check cache first (cache for 6 hours)
        $cacheKey = 'woolworths_' . md5($productName);

        if (Cache::has($cacheKey)) {
            Log::info('Woolworths scraper: Using cached result', ['product' => $productName]);
            return Cache::get($cacheKey);
        }

        try {
            // Rate limiting: wait 2 seconds between requests
            sleep(2);

            $searchQuery = urlencode($productName);
            $url = $this->searchUrl . $searchQuery;

            Log::info('Woolworths scraper: Fetching', ['url' => $url]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-ZA,en;q=0.9',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Woolworths scraper: Request failed', [
                    'status' => $response->status(),
                    'product' => $productName
                ]);
                return null;
            }

            $html = $response->body();
            $result = $this->parseProductPage($html, $productName);

            if ($result) {
                // Cache successful result
                Cache::put($cacheKey, $result, now()->addHours(6));
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Woolworths scraper: Exception', [
                'product' => $productName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse HTML to extract product information
     *
     * @param string $html
     * @param string $searchTerm
     * @return array|null
     */
    protected function parseProductPage(string $html, string $searchTerm)
    {
        try {
            // Method 1: Try to find JSON-LD structured data
            if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);
                if (isset($jsonData['offers']['price'])) {
                    return [
                        'name' => $jsonData['name'] ?? $searchTerm,
                        'price' => floatval($jsonData['offers']['price']),
                        'currency' => $jsonData['offers']['priceCurrency'] ?? 'ZAR',
                        'source' => 'json-ld'
                    ];
                }
            }

            // Method 2: Look for price in common HTML patterns
            $pricePatterns = [
                '/data-price="([0-9.]+)"/i',
                '/class="[^"]*price[^"]*"[^>]*>R?([0-9.]+)/i',
                '/<span[^>]*class="[^"]*amount[^"]*"[^>]*>R?([0-9.]+)/i',
                '/\bR([0-9]+\.[0-9]{2})\b/i',
            ];

            foreach ($pricePatterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $price = floatval(str_replace(',', '', $matches[1]));

                    // Sanity check: price should be reasonable (R1 - R1000)
                    if ($price >= 1 && $price <= 1000) {
                        return [
                            'name' => $searchTerm,
                            'price' => $price,
                            'currency' => 'ZAR',
                            'source' => 'html-parse'
                        ];
                    }
                }
            }

            // Method 3: Try to find product data in JavaScript
            if (preg_match('/window\.__INITIAL_STATE__\s*=\s*({.*?});/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);
                // Navigate the JSON structure to find price
                // This structure varies, so we'd need to inspect the actual response
            }

            Log::warning('Woolworths scraper: Could not parse price', [
                'product' => $searchTerm,
                'html_length' => strlen($html)
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Woolworths scraper: Parse exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update all foods from Woolworths
     *
     * @param bool $forceUpdate Skip cache
     * @return array ['updated' => int, 'failed' => int, 'skipped' => int]
     */
    public function updateAllFoodPrices($forceUpdate = false)
    {
        $foods = Food::where('source', 'woolworths')
            ->orWhere('source', 'manual') // Try to update manual ones too
            ->get();

        $stats = [
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        foreach ($foods as $food) {
            if ($forceUpdate) {
                $cacheKey = 'woolworths_' . md5($food->name);
                Cache::forget($cacheKey);
            }

            $productData = $this->searchProduct($food->name);

            if ($productData && isset($productData['price'])) {
                $oldPrice = $food->cost;
                $newPrice = $productData['price'];

                // Only update if price changed significantly (more than 5% or R0.50)
                $priceDiff = abs($newPrice - $oldPrice);
                $percentDiff = ($priceDiff / $oldPrice) * 100;

                if ($priceDiff > 0.50 || $percentDiff > 5) {
                    $food->update([
                        'cost' => $newPrice,
                        'price_updated_at' => now(),
                        'source' => 'woolworths'
                    ]);

                    Log::info('Woolworths scraper: Price updated', [
                        'food' => $food->name,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice
                    ]);

                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            } else {
                Log::warning('Woolworths scraper: Failed to get price', [
                    'food' => $food->name
                ]);
                $stats['failed']++;
            }

            // Rate limiting: wait between foods
            sleep(2);
        }

        return $stats;
    }

    /**
     * Test a single product search
     *
     * @param string $productName
     * @return void
     */
    public function test(string $productName)
    {
        echo "Testing Woolworths scraper for: {$productName}\n";
        echo str_repeat('-', 50) . "\n";

        $result = $this->searchProduct($productName);

        if ($result) {
            echo "✓ Success!\n";
            echo "Name: {$result['name']}\n";
            echo "Price: R{$result['price']}\n";
            echo "Currency: {$result['currency']}\n";
            echo "Source: {$result['source']}\n";
        } else {
            echo "✗ Failed to find product or extract price\n";
        }

        echo str_repeat('-', 50) . "\n";
    }
}
