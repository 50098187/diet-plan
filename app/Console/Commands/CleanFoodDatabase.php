<?php

namespace App\Console\Commands;

use App\Models\Food;
use Illuminate\Console\Command;

class CleanFoodDatabase extends Command
{
    protected $signature = 'food:clean';
    protected $description = 'Clean food database to keep only the 20 seeded foods';

    public function handle()
    {
        $keepFoods = [
            'Eggs',
            'Salmon',
            'Chicken breast',
            'Beef (lean)',
            'Liver (beef)',
            'Liver (chicken)',
            'Greek yogurt (plain, 2%)',
            'Oats',
            'Whole grain bread',
            'Brown rice (cooked)',
            'Lentils (dry)',
            'Chickpeas (cooked)',
            'Spinach (cooked)',
            'Broccoli (cooked)',
            'Bell peppers',
            'Sweet potato',
            'Berries (mixed)',
            'Banana',
            'Avocado',
            'Almonds',
        ];

        $deleted = Food::whereNotIn('name', $keepFoods)->delete();

        $this->info("Deleted {$deleted} duplicate/old food entries");
        $this->info("Kept only the 20 foods from the seeder");

        return 0;
    }
}
