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
        Schema::create('price_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('reported_price', 8, 2);
            $table->string('store_location')->nullable(); // e.g., "Woolworths, Sandton City"
            $table->string('store_chain')->default('Woolworths'); // Woolworths, Pick n Pay, Checkers
            $table->text('notes')->nullable(); // Optional notes from user
            $table->boolean('verified')->default(false); // Admin verification
            $table->timestamp('reported_at');
            $table->timestamps();

            // Index for faster queries
            $table->index(['food_id', 'verified', 'reported_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_reports');
    }
};
