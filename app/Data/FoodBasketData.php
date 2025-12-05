<?php

namespace App\Data;

/**
 * South African Food Basket Data
 * Source: Official SA food basket survey
 * All prices and nutritional information as provided
 */
class FoodBasketData
{
    /**
     * Get all food basket items as a constant matrix
     *
     * @return array
     */
    public static function getFoods(): array
    {
        return [
            // Protein Sources
            [
                'name' => 'Eggs',
                'serving_size' => '1 large (50 g)',
                'protein' => 6.3,
                'carbs' => 0.6,
                'fat' => 5.3,
                'fiber' => 0,
                'energy_kj' => 313,
                'cost' => 3.63, // 65.29 / 18 eggs
                'package_price' => 65.29,
                'package_size' => '1.5 dozen',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Full cream milk (long life)',
                'serving_size' => '100 ml',
                'protein' => 3.9,
                'carbs' => 4,
                'fat' => 3.4,
                'fiber' => 0,
                'energy_kj' => 264,
                'cost' => 2.00, // 20.04 / 10
                'package_price' => 20.04,
                'package_size' => '1 litre',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Cheddar cheese',
                'serving_size' => '100 g',
                'protein' => 24.7,
                'carbs' => 0.2,
                'fat' => 31.4,
                'fiber' => 0,
                'energy_kj' => 1582,
                'cost' => 15.40, // 153.99 / 10
                'package_price' => 153.99,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Beef mince',
                'serving_size' => '100 g',
                'protein' => 19,
                'carbs' => 0,
                'fat' => 13,
                'fiber' => 0,
                'energy_kj' => 837,
                'cost' => 11.79, // 117.92 / 10
                'package_price' => 117.92,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Fish (tinned)',
                'serving_size' => '1 cup drained',
                'protein' => 33.63,
                'carbs' => 0,
                'fat' => 3.49,
                'fiber' => 0,
                'energy_kj' => 732,
                'cost' => 7.11, // 28.43 / 4 cups per 400g
                'package_price' => 28.43,
                'package_size' => '400g',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'IQF chicken portions',
                'serving_size' => '100 g',
                'protein' => 21.39,
                'carbs' => 0,
                'fat' => 3.08,
                'fiber' => 0,
                'energy_kj' => 498,
                'cost' => 4.82, // 96.38 / 20
                'package_price' => 96.38,
                'package_size' => '2 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Polony',
                'serving_size' => '100 g',
                'protein' => 13.5,
                'carbs' => 4,
                'fat' => 5.4,
                'fiber' => 0,
                'energy_kj' => 510,
                'cost' => 5.72, // 57.24 / 10
                'package_price' => 57.24,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],

            // Carbohydrates
            [
                'name' => 'Brown bread',
                'serving_size' => '1 slice',
                'protein' => 2.91,
                'carbs' => 13.83,
                'fat' => 1.26,
                'fiber' => 0,
                'energy_kj' => 309,
                'cost' => 0.62, // 17.42 / 28 slices
                'package_price' => 17.42,
                'package_size' => '700g',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Maize meal',
                'serving_size' => '100 g',
                'protein' => 7.4,
                'carbs' => 73.00,
                'fat' => 1,
                'fiber' => 0,
                'energy_kj' => 1335,
                'cost' => 1.54, // 77.13 / 50
                'package_price' => 77.13,
                'package_size' => '5 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Rice',
                'serving_size' => '100 g',
                'protein' => 2.64,
                'carbs' => 27.64,
                'fat' => 1.07,
                'fiber' => 0,
                'energy_kj' => 565,
                'cost' => 2.19, // 43.88 / 20
                'package_price' => 43.88,
                'package_size' => '2 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Baked beans (tinned)',
                'serving_size' => '100 g',
                'protein' => 6.03,
                'carbs' => 16.56,
                'fat' => 0.29,
                'fiber' => 0,
                'energy_kj' => 381,
                'cost' => 3.90, // 15.97 / 4.1
                'package_price' => 15.97,
                'package_size' => '410g',
                'source' => 'sa_food_basket',
            ],

            // Vegetables
            [
                'name' => 'Onions',
                'serving_size' => '100 g',
                'protein' => 0.92,
                'carbs' => 10.11,
                'fat' => 0.08,
                'fiber' => 0,
                'energy_kj' => 176,
                'cost' => 2.70, // 27.00 / 10
                'package_price' => 27.00,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Potatoes',
                'serving_size' => '100 g',
                'protein' => 2,
                'carbs' => 17.5,
                'fat' => 0.09,
                'fiber' => 0,
                'energy_kj' => 322,
                'cost' => 2.13, // 21.32 / 10
                'package_price' => 21.32,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Tomatoes',
                'serving_size' => '1 medium (120 g)',
                'protein' => 1.08,
                'carbs' => 5,
                'fat' => 0.25,
                'fiber' => 0,
                'energy_kj' => 93,
                'cost' => 3.83, // 31.91 / 1000 * 120
                'package_price' => 31.91,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Cabbage',
                'serving_size' => '100 g',
                'protein' => 1.44,
                'carbs' => 5.58,
                'fat' => 0.12,
                'fiber' => 0,
                'energy_kj' => 100,
                'cost' => 2.39, // 23.93 / 10 (assuming ~1kg cabbage)
                'package_price' => 23.93,
                'package_size' => '1 head (~1kg)',
                'source' => 'sa_food_basket',
            ],

            // Fruits
            [
                'name' => 'Apples',
                'serving_size' => '1 medium (450 g)',
                'protein' => 0.36,
                'carbs' => 19.06,
                'fat' => 0.23,
                'fiber' => 0,
                'energy_kj' => 300,
                'cost' => 12.60, // 28 / 1000 * 450
                'package_price' => 28.00,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Bananas',
                'serving_size' => '1 medium (120 g)',
                'protein' => 1.3,
                'carbs' => 27,
                'fat' => 0.39,
                'fiber' => 0,
                'energy_kj' => 439,
                'cost' => 2.32, // 19.32 / 1000 * 120
                'package_price' => 19.32,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],
            [
                'name' => 'Oranges',
                'serving_size' => '1 medium (154 g)',
                'protein' => 1.23,
                'carbs' => 15.39,
                'fat' => 0.16,
                'fiber' => 0,
                'energy_kj' => 258,
                'cost' => 3.50, // 22.73 / 1000 * 154
                'package_price' => 22.73,
                'package_size' => '1 kg',
                'source' => 'sa_food_basket',
            ],
        ];
    }

    /**
     * Get count of foods in basket
     *
     * @return int
     */
    public static function count(): int
    {
        return count(self::getFoods());
    }

    /**
     * Get food by index (0-based)
     *
     * @param int $index
     * @return array|null
     */
    public static function getFood(int $index): ?array
    {
        $foods = self::getFoods();
        return $foods[$index] ?? null;
    }

    /**
     * Get food by name
     *
     * @param string $name
     * @return array|null
     */
    public static function findByName(string $name): ?array
    {
        $foods = self::getFoods();
        foreach ($foods as $food) {
            if (strcasecmp($food['name'], $name) === 0) {
                return $food;
            }
        }
        return null;
    }
}
