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
            $table->string('api_id')->nullable()->after('id');
            $table->string('source')->default('manual')->after('api_id'); // manual, woolworths, etc
            $table->decimal('calories', 8, 2)->nullable()->after('energy_kj'); // in kcal
            $table->timestamp('price_updated_at')->nullable()->after('cost');
            $table->boolean('is_active')->default(true)->after('price_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn(['api_id', 'source', 'calories', 'price_updated_at', 'is_active']);
        });
    }
};
