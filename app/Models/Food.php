<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';

    protected $fillable = [
        'name',
        'serving_size',
        'protein',
        'carbs',
        'fat',
        'fiber',
        'energy_kj',
        'calories',
        'cost',
        'packages',
        'api_id',
        'source',
        'price_updated_at',
        'is_active',
    ];

    protected $casts = [
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fat' => 'decimal:2',
        'fiber' => 'decimal:2',
        'energy_kj' => 'decimal:2',
        'calories' => 'decimal:2',
        'cost' => 'decimal:2',
        'packages' => 'array',
        'price_updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active foods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get price reports for this food
     */
    public function priceReports()
    {
        return $this->hasMany(PriceReport::class);
    }

    /**
     * Get average reported price from recent verified reports
     */
    public function getAverageReportedPrice($days = 7)
    {
        return $this->priceReports()
            ->verified()
            ->recent($days)
            ->avg('reported_price');
    }

    /**
     * Update food price from crowd-sourced data
     */
    public function updateFromCrowdSource()
    {
        $avgPrice = $this->getAverageReportedPrice();

        if ($avgPrice) {
            $this->update([
                'cost' => $avgPrice,
                'price_updated_at' => now(),
                'source' => 'crowd-sourced'
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get the cheapest package per gram
     *
     * @return array|null ['size' => '400g', 'price' => 45.99, 'price_per_gram' => 0.115]
     */
    public function getCheapestPackage()
    {
        if (!$this->packages || count($this->packages) === 0) {
            return null;
        }

        return collect($this->packages)
            ->sortBy('price_per_gram')
            ->first();
    }

    /**
     * Calculate best packages to buy for a given weight in grams
     *
     * @param float $gramsNeeded
     * @return array ['packages' => [{size, quantity, total_price}], 'total_grams' => int, 'total_price' => float]
     */
    public function calculatePackagesToBuy(float $gramsNeeded)
    {
        if (!$this->packages || count($this->packages) === 0) {
            return null;
        }

        // Sort packages by size (largest first) for greedy algorithm
        $packages = collect($this->packages)
            ->sortByDesc(function ($pkg) {
                return $this->extractGrams($pkg['size']);
            })
            ->values()
            ->toArray();

        $result = [];
        $remaining = $gramsNeeded;
        $totalPrice = 0;
        $totalGrams = 0;

        // Greedy algorithm: buy largest packages first
        foreach ($packages as $pkg) {
            $pkgGrams = $this->extractGrams($pkg['size']);

            if ($remaining >= $pkgGrams) {
                $quantity = floor($remaining / $pkgGrams);
                $result[] = [
                    'size' => $pkg['size'],
                    'quantity' => $quantity,
                    'price_each' => $pkg['price'],
                    'total_price' => $quantity * $pkg['price']
                ];
                $remaining -= $quantity * $pkgGrams;
                $totalPrice += $quantity * $pkg['price'];
                $totalGrams += $quantity * $pkgGrams;
            }
        }

        // If there's still remaining, buy one more of the smallest package
        if ($remaining > 0) {
            $smallestPkg = end($packages);
            $result[] = [
                'size' => $smallestPkg['size'],
                'quantity' => 1,
                'price_each' => $smallestPkg['price'],
                'total_price' => $smallestPkg['price']
            ];
            $totalPrice += $smallestPkg['price'];
            $totalGrams += $this->extractGrams($smallestPkg['size']);
        }

        return [
            'packages' => $result,
            'total_grams' => $totalGrams,
            'total_price' => round($totalPrice, 2)
        ];
    }

    /**
     * Extract grams from size string (e.g., "400g" => 400, "1kg" => 1000)
     *
     * @param string $size
     * @return float
     */
    protected function extractGrams(string $size)
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*kg/i', $size, $matches)) {
            return floatval($matches[1]) * 1000;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*g/i', $size, $matches)) {
            return floatval($matches[1]);
        }

        // Default: assume it's grams
        return floatval($size);
    }

    /**
     * Update the cost field to reflect the cheapest per-serving option
     */
    public function updateCostFromPackages()
    {
        $cheapest = $this->getCheapestPackage();

        if ($cheapest) {
            // Extract grams from serving size
            $servingGrams = $this->extractGrams($this->serving_size);

            // Calculate cost per serving
            $costPerServing = $cheapest['price_per_gram'] * $servingGrams;

            $this->update([
                'cost' => round($costPerServing, 2)
            ]);
        }
    }
}
