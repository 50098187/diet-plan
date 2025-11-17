<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Checkers South Africa Web Scraper
 *
 * ⚠️ LEGAL WARNING:
 * This service scrapes Checkers.co.za which may violate their Terms of Service.
 * Use at your own risk. You are responsible for any legal consequences.
 *
 * ETHICAL USAGE:
 * - Rate limited to 1 request per 2 seconds
 * - Caches results to minimize requests
 * - Respectful of server resources
 * - For personal/educational use only
 *
 * NOTES:
 * - Checkers uses anti-bot protection (403 errors common)
 * - May require browser automation (Selenium) for reliable scraping
 * - Consider using crowd-sourced pricing as primary source
 */
class CheckersScraperService
{
    protected $baseUrl = 'https://www.checkers.co.za';
    protected $searchUrl = 'https://www.checkers.co.za/search?q=';

    /**
     * Search for a product and get its price
     *
     * @param string $productName
     * @return array|null ['name', 'price', 'url']
     */
    public function searchProduct(string $productName)
    {
        // Check cache first (cache for 6 hours)
        $cacheKey = 'checkers_' . md5($productName);

        if (Cache::has($cacheKey)) {
            Log::info('Checkers scraper: Using cached result', ['product' => $productName]);
            return Cache::get($cacheKey);
        }

        try {
            // Rate limiting: wait 2 seconds between requests
            sleep(2);

            $searchQuery = urlencode($productName);
            $url = $this->searchUrl . $searchQuery;

            Log::info('Checkers scraper: Fetching', ['url' => $url]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-ZA,en;q=0.9',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Referer' => 'https://www.checkers.co.za/',
                    'DNT' => '1',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                ])
                ->get($url);

            if ($response->status() === 403) {
                Log::warning('Checkers scraper: Anti-bot protection detected (403)', [
                    'product' => $productName,
                    'suggestion' => 'Consider using Selenium/browser automation or crowd-sourced pricing'
                ]);
                return null;
            }

            if (!$response->successful()) {
                Log::warning('Checkers scraper: Request failed', [
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
            Log::error('Checkers scraper: Exception', [
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

                // Handle array of products
                if (is_array($jsonData) && isset($jsonData[0])) {
                    $jsonData = $jsonData[0];
                }

                if (isset($jsonData['offers']['price'])) {
                    return [
                        'name' => $jsonData['name'] ?? $searchTerm,
                        'price' => floatval($jsonData['offers']['price']),
                        'currency' => $jsonData['offers']['priceCurrency'] ?? 'ZAR',
                        'source' => 'json-ld'
                    ];
                }
            }

            // Method 2: Look for Sixty60/Checkers specific price patterns
            $pricePatterns = [
                // Common e-commerce patterns
                '/data-price="([0-9.]+)"/i',
                '/class="[^"]*price[^"]*"[^>]*>R\s*([0-9.,]+)/i',
                '/<span[^>]*class="[^"]*price[^"]*"[^>]*>R\s*([0-9.,]+)/i',
                '/price["\s:]+([0-9]+\.[0-9]{2})/i',
                '/R\s*([0-9]+\.[0-9]{2})\b/i',

                // Sixty60 specific patterns (may need adjustment)
                '/"price"\s*:\s*([0-9.]+)/i',
                '/"currentPrice"\s*:\s*([0-9.]+)/i',
                '/"amount"\s*:\s*([0-9.]+)/i',
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

            // Method 3: Try to find product data in JavaScript state
            $jsPatterns = [
                '/window\.__INITIAL_STATE__\s*=\s*({.*?});/s',
                '/window\.__APOLLO_STATE__\s*=\s*({.*?});/s',
                '/__NEXT_DATA__\s*=\s*({.*?})</s',
            ];

            foreach ($jsPatterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    try {
                        $jsonData = json_decode($matches[1], true);
                        if ($jsonData) {
                            // Try to find price in nested structure
                            $price = $this->findPriceInNestedArray($jsonData);
                            if ($price && $price >= 1 && $price <= 1000) {
                                return [
                                    'name' => $searchTerm,
                                    'price' => $price,
                                    'currency' => 'ZAR',
                                    'source' => 'js-state'
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue to next pattern
                        continue;
                    }
                }
            }

            Log::warning('Checkers scraper: Could not parse price', [
                'product' => $searchTerm,
                'html_length' => strlen($html)
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Checkers scraper: Parse exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Recursively search for price in nested array
     *
     * @param array $data
     * @param int $depth
     * @return float|null
     */
    protected function findPriceInNestedArray(array $data, int $depth = 0)
    {
        // Prevent infinite recursion
        if ($depth > 5) {
            return null;
        }

        // Look for common price field names
        $priceKeys = ['price', 'currentPrice', 'sellingPrice', 'amount', 'value'];

        foreach ($priceKeys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return floatval($data[$key]);
            }
        }

        // Recursively search nested arrays
        foreach ($data as $value) {
            if (is_array($value)) {
                $price = $this->findPriceInNestedArray($value, $depth + 1);
                if ($price !== null) {
                    return $price;
                }
            }
        }

        return null;
    }

    /**
     * Update all foods from Checkers
     *
     * @param bool $forceUpdate Skip cache
     * @return array ['updated' => int, 'failed' => int, 'skipped' => int]
     */
    public function updateAllFoodPrices($forceUpdate = false)
    {
        $foods = Food::where('source', 'checkers')
            ->orWhere('source', 'manual') // Try to update manual ones too
            ->get();

        $stats = [
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'blocked' => 0
        ];

        foreach ($foods as $food) {
            if ($forceUpdate) {
                $cacheKey = 'checkers_' . md5($food->name);
                Cache::forget($cacheKey);
            }

            $productData = $this->searchProduct($food->name);

            if ($productData && isset($productData['price'])) {
                $oldPrice = $food->cost;
                $newPrice = $productData['price'];

                // Only update if price changed significantly (more than 5% or R0.50)
                $priceDiff = abs($newPrice - $oldPrice);
                $percentDiff = $oldPrice > 0 ? ($priceDiff / $oldPrice) * 100 : 100;

                if ($priceDiff > 0.50 || $percentDiff > 5) {
                    $food->update([
                        'cost' => $newPrice,
                        'price_updated_at' => now(),
                        'source' => 'checkers'
                    ]);

                    Log::info('Checkers scraper: Price updated', [
                        'food' => $food->name,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice
                    ]);

                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            } else {
                Log::warning('Checkers scraper: Failed to get price', [
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
        echo "Testing Checkers scraper for: {$productName}\n";
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
            echo "\nCommon issues:\n";
            echo "  - Anti-bot protection (403 error)\n";
            echo "  - Product not found\n";
            echo "  - Price extraction failed\n";
            echo "\nSuggestion: Use crowd-sourced pricing for more reliable data\n";
        }

        echo str_repeat('-', 50) . "\n";
    }
}
