<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'foods';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category',
        'product',
        'price_per_unit',
        'protein',
        'carbs',
        'fat',
        'energy_kj',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fat' => 'decimal:2',
        'energy_kj' => 'decimal:2',
    ];

    /**
     * Get calories from energy (kJ)
     * 1 kcal = 4.184 kJ
     *
     * @return float
     */
    public function getCaloriesAttribute(): float
    {
        return round($this->energy_kj / 4.184, 2);
    }
}
