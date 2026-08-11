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
        $skipped = 0;
        $lastCategory = '';

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            // Debug: show what we're processing
            if (empty($row[0]) && !empty($lastCategory)) {
                // Use last category if current category is empty
                $category = $lastCategory;
            } else {
                $category = trim($row[0] ?? '');
                if (!empty($category)) {
                    $lastCategory = $category;
                }
            }

            $product = trim($row[1] ?? '');

            // Skip empty rows and total rows
            if (empty($product) ||
                stripos($product, 'Total') !== false ||
                stripos($product, 'NAMC') !== false ||
                stripos($product, 'Category') !== false) {
                $skipped++;
                continue;
            }

            $price = (float) str_replace(',', '.', $row[2] ?? '0');
            $protein = (float) str_replace(',', '.', $row[3] ?? '0');
            $carbs = (float) str_replace(',', '.', $row[4] ?? '0');
            $fat = (float) str_replace(',', '.', $row[5] ?? '0');
            $energy_kj = (float) str_replace(',', '.', $row[6] ?? '0');

            // Skip if essential data is missing (but show why)
            if ($price <= 0) {
                $this->command->warn("Skipping '{$product}' - no price (price={$price})");
                $skipped++;
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
            $this->command->line("✓ Added: [{$category}] {$product} - R{$price}");
        }

        fclose($file);

        $this->command->info("Successfully loaded {$count} food items from CSV.");
        $this->command->info("Skipped {$skipped} rows (headers, totals, or missing data).");
    }
}
