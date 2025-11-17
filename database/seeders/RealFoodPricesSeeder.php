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
        $foods = [
            // Woolworths
            ['Chicken Breast Fillets', '1kg', 31.0, 0, 3.6, 0, 694, 119.99, 'woolworths'],
            ['Brown Rice', '1kg', 7.5, 77.0, 2.8, 3.5, 1555, 34.99, 'woolworths'],
            ['Free Range Eggs', '6 pack', 38.0, 2.4, 32.0, 0, 1880, 45.99, 'woolworths'],
            ['Fresh Atlantic Salmon', '200g', 20.0, 0, 13.0, 0, 828, 89.99, 'woolworths'],
            ['Oats', '500g', 13.5, 58.0, 6.5, 8.0, 1380, 24.99, 'woolworths'],
            ['Beef Mince (Lean)', '500g', 26.5, 0, 15.0, 0, 1050, 64.99, 'woolworths'],
            ['Greek Yoghurt Plain', '500g', 10.0, 4.0, 10.0, 0, 580, 39.99, 'woolworths'],
            ['Whole Wheat Bread', '700g', 9.0, 44.0, 3.0, 6.0, 1050, 18.99, 'woolworths'],
            ['Red Lentils', '500g', 24.0, 60.0, 1.0, 15.5, 1420, 29.99, 'woolworths'],

            // Checkers
            ['Chicken Breast Portions', '1kg', 31.0, 0, 3.6, 0, 694, 109.99, 'checkers'],
            ['Brown Rice', '1kg', 7.5, 77.0, 2.8, 3.5, 1555, 32.99, 'checkers'],
            ['Large Eggs', '6 pack', 38.0, 2.4, 32.0, 0, 1880, 42.99, 'checkers'],
            ['Frozen Salmon Portions', '400g', 20.0, 0, 13.0, 0, 828, 79.99, 'checkers'],
            ['Rolled Oats', '1kg', 13.5, 58.0, 6.5, 8.0, 1380, 39.99, 'checkers'],
            ['Beef Mince', '500g', 26.5, 0, 15.0, 0, 1050, 59.99, 'checkers'],
            ['Plain Yoghurt', '1kg', 10.0, 4.0, 10.0, 0, 580, 45.99, 'checkers'],
            ['Brown Bread', '700g', 9.0, 44.0, 3.0, 6.0, 1050, 16.99, 'checkers'],
            ['Lentils', '500g', 24.0, 60.0, 1.0, 15.5, 1420, 27.99, 'checkers'],
            ['Broccoli Fresh', '500g', 2.8, 7.0, 0.4, 2.6, 140, 19.99, 'checkers'],
            ['Baby Spinach', '250g', 2.9, 3.6, 0.4, 2.2, 96, 24.99, 'checkers'],
            ['Sweet Potato', '1kg', 1.7, 20.0, 0.1, 3.0, 360, 29.99, 'checkers'],
            ['Bananas', '1kg', 1.1, 23.0, 0.3, 2.6, 371, 19.99, 'checkers'],
            ['Almonds', '200g', 21.0, 22.0, 49.0, 12.5, 2440, 69.99, 'checkers'],
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
