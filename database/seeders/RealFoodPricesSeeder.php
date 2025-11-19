<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Seeder;

class RealFoodPricesSeeder extends Seeder
{
    /**
     * Seed the database with realistic food prices from SA stores
     */
    public function run(): void
    {
        // Original 20 foods from the book with real SA store prices
        // Cost is calculated as: (serving grams / package grams) * package price
        $foods = [
            // Proteins
            ['Eggs', '1 large egg (50 g)', 6.3, 0.6, 5.3, 0, 313, 3.50, 'woolworths'], // R21/6 eggs
            ['Salmon', '150 g fillet', 31.0, 0, 7.0, 0, 786, 26.99, 'woolworths'], // R89.99/500g pack
            ['Chicken breast', '150 g', 33.0, 0, 3.6, 0, 694, 17.99, 'checkers'], // R59.99/500g pack
            ['Beef (lean)', '150 g', 31.0, 0, 10.0, 0, 897, 19.49, 'woolworths'], // R64.99/500g pack
            ['Liver (beef)', '100 g', 20.4, 3.9, 3.6, 0, 546, 6.25, 'checkers'], // R24.99/400g pack
            ['Liver (chicken)', '100 g', 17.0, 1.0, 5.0, 0, 491, 4.99, 'checkers'], // R19.99/400g pack

            // Dairy
            ['Greek yogurt (plain, 2%)', '3/4 cup (150 g)', 10.0, 5.0, 3.0, 0, 366, 5.99, 'woolworths'], // R19.99/500g tub

            // Grains & Carbs
            ['Oats', '1/2 cup (40 g)', 5.0, 27.0, 3.0, 4.0, 687, 0.99, 'woolworths'], // R24.99/1kg
            ['Whole grain bread', '1 slice (40 g)', 4.5, 20.0, 1.0, 3.0, 478, 1.08, 'checkers'], // R18.99/700g loaf
            ['Brown rice (cooked)', '1/2 cup cooked (100 g)', 2.6, 23.0, 0.9, 1.8, 426, 1.65, 'woolworths'], // R32.99/2kg

            // Legumes
            ['Lentils (dry)', '1/2 cup dry (100 g)', 25.0, 60.0, 1.0, 11.0, 1570, 5.99, 'checkers'], // R29.99/500g pack
            ['Chickpeas (cooked)', '1/2 cup cooked (100 g)', 8.9, 27.0, 2.6, 7.6, 634, 6.99, 'checkers'], // R27.99/400g can

            // Vegetables
            ['Spinach (cooked)', '1/2 cup cooked (90 g)', 2.9, 3.7, 0.4, 2.2, 115, 8.33, 'woolworths'], // R24.99/270g bag
            ['Broccoli (cooked)', '1/2 cup cooked (90 g)', 2.8, 6.0, 0.3, 2.6, 140, 4.99, 'checkers'], // R19.99/360g pack
            ['Bell peppers', '1 medium pepper (120 g)', 1.0, 6.0, 0.3, 2.0, 122, 14.99, 'checkers'], // R14.99 per pepper
            ['Sweet potato', '1 medium (150 g)', 2.0, 27.0, 0.1, 4.0, 511, 14.99, 'woolworths'], // R29.99/300g (2 potatoes)

            // Fruits
            ['Berries (mixed)', '1 cup (150 g)', 1.0, 17.0, 0.5, 4.0, 295, 13.33, 'woolworths'], // R39.99/450g pack
            ['Banana', '1 medium (120 g)', 1.3, 27.0, 0.3, 3.1, 491, 3.32, 'checkers'], // R19.99/720g (6 bananas)

            // Healthy Fats
            ['Avocado', '1/2 medium (80 g)', 1.0, 6.0, 15.0, 5.0, 676, 17.49, 'woolworths'], // R34.99/160g (1 avocado)
            ['Almonds', '1/4 cup (30 g)', 6.0, 6.0, 14.0, 3.5, 818, 5.24, 'checkers'], // R34.99/200g pack
        ];

        foreach ($foods as $data) {
            Food::updateOrCreate(
                [
                    'name' => $data[0],
                    'source' => $data[8]
                ],
                [
                    'serving_size' => $data[1],
                    'protein' => $data[2],
                    'carbs' => $data[3],
                    'fat' => $data[4],
                    'fiber' => $data[5],
                    'energy_kj' => $data[6],
                    'calories' => round($data[6] / 4.184, 2),
                    'cost' => $data[7],
                    'price_updated_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Seeded ' . count($foods) . ' foods with real SA store prices');
    }
}
