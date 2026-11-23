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
        // Стоимость тренировки нужна для имитации оплаты и отображения цены
        if (! Schema::hasColumn('workouts', 'price')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->default(0)->after('current_participants');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('workouts', 'price')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
