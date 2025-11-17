<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $foodData = [
            ['Eggs', '1 large (50 g)', 6.3, 0.6, 5.3, 0, 313, 5],
            ['Salmon', '150 g fillet', 31, 0, 7, 0, 786, 5],
            ['Oats', '40 g', 5, 27, 3, 4, 687, 5],
            ['Chicken breast', '150 g', 33, 0, 3.6, 0, 694, 5],
            ['Beef (lean)', '150 g', 31, 0, 10, 0, 897, 5],
            ['Liver (beef)', '100 g', 20.4, 3.9, 3.6, 0, 546, 5],
            ['Liver (chicken)', '100 g', 17, 1, 5, 0, 491, 5],
            ['Greek yogurt (plain, 2%)', '150 g', 10, 5, 3, 0, 366, 5],
            ['Whole grain bread', '1 slice (40 g)', 4.5, 20, 1, 3, 478, 5],
            ['Lentils (dry)', '100 g', 25, 60, 1, 11, 1570, 5],
            ['Bell peppers', '120 g', 1, 6, 0.3, 2, 122, 5],
            ['Chickpeas (cooked)', '100 g', 8.9, 27, 2.6, 7.6, 634, 5],
            ['Brown rice (cooked)', '100 g', 2.6, 23, 0.9, 1.8, 426, 5],
            ['Spinach (cooked)', '90 g', 2.9, 3.7, 0.4, 2.2, 115, 5],
            ['Broccoli (cooked)', '90 g', 2.8, 6, 0.3, 2.6, 140, 5],
            ['Sweet potato', '1 medium (150 g)', 2, 27, 0.1, 4, 511, 5],
            ['Berries (mixed)', '150 g', 1, 17, 0.5, 4, 295, 5],
            ['Banana', '1 medium (120 g)', 1.3, 27, 0.3, 3.1, 491, 5],
            ['Avocado', '1/2 medium (80 g)', 1, 6, 15, 5, 676, 5],
            ['Almonds', '30 g', 6, 6, 14, 3.5, 818, 5],
        ];

        foreach ($foodData as $data) {
            Food::create([
                'name' => $data[0],
                'serving_size' => $data[1],
                'protein' => $data[2],
                'carbs' => $data[3],
                'fat' => $data[4],
                'fiber' => $data[5],
                'energy_kj' => $data[6],
                'calories' => round($data[6] / 4.184, 2), // Convert kJ to kcal
                'cost' => $data[7],
                'price_updated_at' => now(),
                'is_active' => true,
            ]);
        }
    }
}
