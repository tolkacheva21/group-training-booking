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
        Schema::table('workouts', function (Blueprint $table) {
            // Отменённая тренировка не занимает зал, тренера и инвентарь
            $table->timestamp('cancelled_at')->nullable()->after('workout_end');
            $table->index(['workout_start', 'workout_end']);
            $table->index('trainer_id');
            $table->index('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropIndex(['workout_start', 'workout_end']);
            $table->dropIndex(['trainer_id']);
            $table->dropIndex(['room_id']);
            $table->dropColumn('cancelled_at');
        });
    }
};
