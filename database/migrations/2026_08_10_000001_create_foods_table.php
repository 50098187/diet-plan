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
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('product');
            $table->decimal('price_per_unit', 10, 2); // November-25 (R/unit)
            $table->decimal('protein', 10, 2); // Protein (g) per unit
            $table->decimal('carbs', 10, 2); // Carbs (g) per unit
            $table->decimal('fat', 10, 2); // Fat (g) per unit
            $table->decimal('energy_kj', 10, 2); // Energy (kJ) per unit
            $table->timestamps();

            // Indexes for faster queries
            $table->index('category');
            $table->index('product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
