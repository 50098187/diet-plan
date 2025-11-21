<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            // JSON field to store multiple package sizes and prices
            // Format: [{"size": "200g", "price": 25.99, "price_per_gram": 0.13, "url": "..."}, ...]
            $table->json('packages')->nullable()->after('cost');

            // Keep existing 'cost' field as the cheapest per-serving option for backward compatibility
            // This will be automatically calculated from packages
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn('packages');
        });
    }
};
