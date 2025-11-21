<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Seeder;

class FoodPackagesSeeder extends Seeder
{
    /**
     * Seed common package sizes with estimated South African prices
     *
     * These are rough estimates - run the scraper to get real prices
     */
    public function run(): void
    {
        $foodPackages = [
            'Eggs' => [
                ['size' => '6 pack', 'price' => 25.99, 'price_per_gram' => 0.087, 'source' => 'manual'],
                ['size' => '12 pack', 'price' => 45.99, 'price_per_gram' => 0.077, 'source' => 'manual'],
                ['size' => '18 pack', 'price' => 64.99, 'price_per_gram' => 0.072, 'source' => 'manual'],
            ],
            'Oats' => [
                ['size' => '500g', 'price' => 24.99, 'price_per_gram' => 0.050, 'source' => 'manual'],
                ['size' => '1kg', 'price' => 44.99, 'price_per_gram' => 0.045, 'source' => 'manual'],
                ['size' => '2kg', 'price' => 79.99, 'price_per_gram' => 0.040, 'source' => 'manual'],
            ],
            'Brown rice (cooked)' => [
                ['size' => '500g', 'price' => 29.99, 'price_per_gram' => 0.060, 'source' => 'manual'],
                ['size' => '1kg', 'price' => 49.99, 'price_per_gram' => 0.050, 'source' => 'manual'],
                ['size' => '2kg', 'price' => 89.99, 'price_per_gram' => 0.045, 'source' => 'manual'],
            ],
            'Chicken breast' => [
                ['size' => '500g', 'price' => 69.99, 'price_per_gram' => 0.140, 'source' => 'manual'],
                ['size' => '1kg', 'price' => 129.99, 'price_per_gram' => 0.130, 'source' => 'manual'],
            ],
            'Almonds' => [
                ['size' => '200g', 'price' => 64.99, 'price_per_gram' => 0.325, 'source' => 'manual'],
                ['size' => '400g', 'price' => 119.99, 'price_per_gram' => 0.300, 'source' => 'manual'],
                ['size' => '750g', 'price' => 209.99, 'price_per_gram' => 0.280, 'source' => 'manual'],
            ],
            'Lentils (dry)' => [
                ['size' => '400g', 'price' => 34.99, 'price_per_gram' => 0.087, 'source' => 'manual'],
                ['size' => '500g', 'price' => 39.99, 'price_per_gram' => 0.080, 'source' => 'manual'],
                ['size' => '1kg', 'price' => 69.99, 'price_per_gram' => 0.070, 'source' => 'manual'],
            ],
            'Greek yogurt (plain, 2%)' => [
                ['size' => '500g', 'price' => 44.99, 'price_per_gram' => 0.090, 'source' => 'manual'],
                ['size' => '1kg', 'price' => 79.99, 'price_per_gram' => 0.080, 'source' => 'manual'],
            ],
            'Banana' => [
                ['size' => '1kg', 'price' => 19.99, 'price_per_gram' => 0.020, 'source' => 'manual'],
                ['size' => '2kg', 'price' => 34.99, 'price_per_gram' => 0.017, 'source' => 'manual'],
            ],
        ];

        foreach ($foodPackages as $foodName => $packages) {
            $food = Food::where('name', 'LIKE', "%{$foodName}%")->first();

            if ($food) {
                $food->update([
                    'packages' => $packages,
                    'price_updated_at' => now()
                ]);

                // Update cost from cheapest package
                $food->updateCostFromPackages();

                $this->command->info("✓ Updated {$foodName} with " . count($packages) . " package sizes");
            } else {
                $this->command->warn("✗ Food '{$foodName}' not found");
            }
        }

        $this->command->newLine();
        $this->command->info('Manual package seeding completed!');
        $this->command->comment('Note: These are estimated prices. Run the scraper to get real store prices.');
    }
}
