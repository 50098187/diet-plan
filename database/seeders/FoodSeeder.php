<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('foods')->truncate();

        // CSV file path
        $csvPath = 'D:\4de jaar 1ste semester\INDE 471\Milestone 6\NAMC foodbasket data and nutritional info.csv';

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $file = fopen($csvPath, 'r');

        // Skip first 3 lines (headers)
        fgets($file); // NAMC food baskets
        fgets($file); // Category;Product;November-25...
        fgets($file); // ;;;Protein...

        $count = 0;

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            // Skip empty rows and total rows
            if (empty($row[0]) || empty($row[1]) ||
                stripos($row[1], 'Total') !== false ||
                stripos($row[1], 'NAMC') !== false) {
                continue;
            }

            $category = trim($row[0]);
            $product = trim($row[1]);
            $price = (float) str_replace(',', '.', $row[2]);
            $protein = (float) str_replace(',', '.', $row[3]);
            $carbs = (float) str_replace(',', '.', $row[4]);
            $fat = (float) str_replace(',', '.', $row[5]);
            $energy_kj = (float) str_replace(',', '.', $row[6]);

            // Skip if essential data is missing
            if (empty($product) || $price <= 0) {
                continue;
            }

            Food::create([
                'category' => $category ?: 'Uncategorised',
                'product' => $product,
                'price_per_unit' => $price,
                'protein' => $protein,
                'carbs' => $carbs,
                'fat' => $fat,
                'energy_kj' => $energy_kj,
            ]);

            $count++;
        }

        fclose($file);

        $this->command->info("Successfully loaded {$count} food items from CSV.");
    }
}
