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
}
