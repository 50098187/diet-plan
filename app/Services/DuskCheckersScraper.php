<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

/**
 * Checkers Browser Automation Scraper using Laravel Dusk
 *
 * ⚠️ LEGAL WARNING:
 * This service scrapes Checkers.co.za which may violate their Terms of Service.
 * Use at your own risk. You are responsible for any legal consequences.
 *
 * REQUIREMENTS:
 * - Laravel Dusk installed: composer require --dev laravel/dusk
 * - Chrome/Chromium browser installed
 * - ChromeDriver (auto-managed by Dusk)
 *
 * ADVANTAGES OVER SIMPLE SCRAPING:
 * - Bypasses anti-bot protection (403 errors)
 * - Handles JavaScript-rendered content
 * - Waits for dynamic content to load
 * - More reliable price extraction
 */
class DuskCheckersScraper
{
    protected $baseUrl = 'https://www.checkers.co.za';
    protected $headless = true;

    /**
     * Search for a product and get its price using browser automation
     *
     * @param string $productName
     * @param bool $useCache
     * @return array|null ['name', 'price', 'currency']
     */
    public function searchProduct(string $productName, bool $useCache = true)
    {
        // Check cache first (cache for 6 hours)
        $cacheKey = 'dusk_checkers_' . md5($productName);

        if ($useCache && Cache::has($cacheKey)) {
            Log::info('Dusk Checkers scraper: Using cached result', ['product' => $productName]);
            return Cache::get($cacheKey);
        }

        try {
            Log::info('Dusk Checkers scraper: Starting browser automation', ['product' => $productName]);

            $result = $this->scrapWithBrowser($productName);

            if ($result) {
                // Cache successful result
                Cache::put($cacheKey, $result, now()->addHours(6));
                Log::info('Dusk Checkers scraper: Success', ['product' => $productName, 'price' => $result['price']]);
            } else {
                Log::warning('Dusk Checkers scraper: No price found', ['product' => $productName]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Dusk Checkers scraper: Exception', [
                'product' => $productName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Scrape product using headless browser
     *
     * @param string $productName
     * @return array|null
     */
    protected function scrapWithBrowser(string $productName)
    {
        $driver = $this->createWebDriver();
        $result = null;

        try {
            $searchUrl = $this->baseUrl . '/search?q=' . urlencode($productName);

            // Navigate to search page
            $driver->get($searchUrl);

            // Wait for page to load (max 10 seconds)
            sleep(3);

            // Get page source after JavaScript execution
            $html = $driver->getPageSource();

            // Try multiple extraction methods
            $result = $this->extractPriceFromHtml($html, $productName);

            if (!$result) {
                // Try to find product cards and extract structured data
                $result = $this->extractFromProductElements($driver, $productName);
            }

        } catch (\Exception $e) {
            Log::error('Dusk browser automation error', [
                'error' => $e->getMessage()
            ]);
        } finally {
            $driver->quit();
        }

        return $result;
    }

    /**
     * Create headless Chrome WebDriver
     *
     * @return RemoteWebDriver
     */
    protected function createWebDriver()
    {
        $options = new ChromeOptions();

        if ($this->headless) {
            $options->addArguments([
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
            ]);
        }

        // Make browser appear more realistic
        $options->addArguments([
            '--window-size=1920,1080',
            '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            '--disable-blink-features=AutomationControlled',
        ]);

        $options->setExperimentalOption('excludeSwitches', ['enable-automation']);
        $options->setExperimentalOption('useAutomationExtension', false);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // Use Dusk's ChromeDriver
        $chromeDriverPath = $this->getChomeDriverPath();

        return RemoteWebDriver::create(
            'http://localhost:9515',
            $capabilities,
            60000, // Connection timeout
            60000  // Request timeout
        );
    }

    /**
     * Get ChromeDriver path (managed by Dusk)
     *
     * @return string
     */
    protected function getChomeDriverPath()
    {
        // Dusk manages ChromeDriver in vendor/laravel/dusk/bin
        return base_path('vendor/laravel/dusk/bin/chromedriver-' . $this->getOS());
    }

    /**
     * Get operating system for ChromeDriver
     *
     * @return string
     */
    protected function getOS()
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            return 'win';
        } elseif (stripos(PHP_OS, 'Darwin') === 0) {
            return 'mac';
        } else {
            return 'linux';
        }
    }

    /**
     * Extract price from HTML source
     *
     * @param string $html
     * @param string $searchTerm
     * @return array|null
     */
    protected function extractPriceFromHtml(string $html, string $searchTerm)
    {
        // Method 1: JSON-LD structured data
        if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
            foreach ($matches[1] as $jsonString) {
                try {
                    $jsonData = json_decode($jsonString, true);

                    if (isset($jsonData['offers']['price'])) {
                        return [
                            'name' => $jsonData['name'] ?? $searchTerm,
                            'price' => floatval($jsonData['offers']['price']),
                            'currency' => $jsonData['offers']['priceCurrency'] ?? 'ZAR',
                            'source' => 'dusk-json-ld'
                        ];
                    }

                    // Handle array of products
                    if (is_array($jsonData) && isset($jsonData[0]['offers']['price'])) {
                        return [
                            'name' => $jsonData[0]['name'] ?? $searchTerm,
                            'price' => floatval($jsonData[0]['offers']['price']),
                            'currency' => $jsonData[0]['offers']['priceCurrency'] ?? 'ZAR',
                            'source' => 'dusk-json-ld'
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Method 2: Price patterns in HTML
        $pricePatterns = [
            '/data-price="([0-9.]+)"/i',
            '/class="[^"]*price[^"]*"[^>]*>R\s*([0-9.,]+)/i',
            '/"price"\s*:\s*"?([0-9.]+)"?/i',
            '/"currentPrice"\s*:\s*"?([0-9.]+)"?/i',
            '/R\s*([0-9]+\.[0-9]{2})\b/i',
        ];

        foreach ($pricePatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = floatval(str_replace(',', '', $matches[1]));

                if ($price >= 1 && $price <= 1000) {
                    return [
                        'name' => $searchTerm,
                        'price' => $price,
                        'currency' => 'ZAR',
                        'source' => 'dusk-html'
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Extract price from product elements using WebDriver
     *
     * @param RemoteWebDriver $driver
     * @param string $searchTerm
     * @return array|null
     */
    protected function extractFromProductElements($driver, string $searchTerm)
    {
        try {
            // Common selectors for product price elements
            $selectors = [
                '.product-price',
                '.price',
                '[data-testid="product-price"]',
                '.product-card__price',
                '.item-price',
            ];

            foreach ($selectors as $selector) {
                try {
                    $elements = $driver->findElements(\Facebook\WebDriver\WebDriverBy::cssSelector($selector));

                    foreach ($elements as $element) {
                        $text = $element->getText();

                        // Extract price from text
                        if (preg_match('/R\s*([0-9.,]+)/i', $text, $matches)) {
                            $price = floatval(str_replace(',', '', $matches[1]));

                            if ($price >= 1 && $price <= 1000) {
                                return [
                                    'name' => $searchTerm,
                                    'price' => $price,
                                    'currency' => 'ZAR',
                                    'source' => 'dusk-element'
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Try next selector
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error extracting from product elements', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Update all foods from Checkers using browser automation
     *
     * @param bool $forceUpdate Skip cache
     * @return array ['updated' => int, 'failed' => int, 'skipped' => int]
     */
    public function updateAllFoodPrices($forceUpdate = false)
    {
        $foods = Food::where('source', 'checkers')
            ->orWhere('source', 'manual')
            ->get();

        $stats = [
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        foreach ($foods as $food) {
            $productData = $this->searchProduct($food->name, !$forceUpdate);

            if ($productData && isset($productData['price'])) {
                $oldPrice = $food->cost;
                $newPrice = $productData['price'];

                // Only update if price changed significantly
                $priceDiff = abs($newPrice - $oldPrice);
                $percentDiff = $oldPrice > 0 ? ($priceDiff / $oldPrice) * 100 : 100;

                if ($priceDiff > 0.50 || $percentDiff > 5) {
                    $food->update([
                        'cost' => $newPrice,
                        'price_updated_at' => now(),
                        'source' => 'checkers'
                    ]);

                    Log::info('Dusk Checkers scraper: Price updated', [
                        'food' => $food->name,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice
                    ]);

                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            } else {
                $stats['failed']++;
            }

            // Rate limiting between products
            sleep(3);
        }

        return $stats;
    }

    /**
     * Test browser automation with a product
     *
     * @param string $productName
     * @return void
     */
    public function test(string $productName)
    {
        echo "Testing Dusk-powered Checkers scraper for: {$productName}\n";
        echo str_repeat('-', 50) . "\n";
        echo "Using browser automation to bypass anti-bot protection\n";
        echo str_repeat('-', 50) . "\n\n";

        $result = $this->searchProduct($productName, false);

        if ($result) {
            echo "✓ Success!\n";
            echo "Name: {$result['name']}\n";
            echo "Price: R{$result['price']}\n";
            echo "Currency: {$result['currency']}\n";
            echo "Source: {$result['source']}\n";
        } else {
            echo "✗ Failed to find product or extract price\n\n";
            echo "Possible issues:\n";
            echo "  - ChromeDriver not installed\n";
            echo "  - Product not found\n";
            echo "  - Selector patterns need updating\n";
        }

        echo str_repeat('-', 50) . "\n";
    }
}
