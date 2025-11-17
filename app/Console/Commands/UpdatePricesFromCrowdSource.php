<?php

namespace App\Console\Commands;

use App\Models\Food;
use Illuminate\Console\Command;

class UpdatePricesFromCrowdSource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foods:update-from-crowdsource {--days=7 : Number of days to look back for price reports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update food prices based on verified crowd-sourced price reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');

        $this->info("Updating prices from crowd-sourced data (last {$days} days)...");
        $this->newLine();

        $foods = Food::active()->get();
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($foods as $food) {
            $avgPrice = $food->getAverageReportedPrice($days);
            $reportCount = $food->priceReports()->verified()->recent($days)->count();

            if ($avgPrice && $reportCount >= 3) { // Require at least 3 verified reports
                $oldPrice = $food->cost;
                $food->update([
                    'cost' => $avgPrice,
                    'price_updated_at' => now(),
                    'source' => 'crowd-sourced'
                ]);

                $this->line(sprintf(
                    "✓ %s: R%.2f → R%.2f (%d reports)",
                    $food->name,
                    $oldPrice,
                    $avgPrice,
                    $reportCount
                ));
                $updatedCount++;
            } else {
                $this->comment(sprintf(
                    "⊘ %s: Insufficient data (%d reports, need 3+)",
                    $food->name,
                    $reportCount
                ));
                $skippedCount++;
            }
        }

        $this->newLine();
        $this->info("✓ Updated: {$updatedCount} foods");
        $this->comment("⊘ Skipped: {$skippedCount} foods (insufficient data)");

        return Command::SUCCESS;
    }
}
