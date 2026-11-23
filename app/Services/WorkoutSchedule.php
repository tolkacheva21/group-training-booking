<?php

namespace App\Services;

use App\Models\Workout;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Проверка конфликтов расписания: один тренер и один зал не могут быть
 * заняты двумя тренировками в пересекающиеся интервалы времени.
 */
class WorkoutSchedule
{
    /**
     * @throws ValidationException
     */
    public function ensureAvailable(
        CarbonInterface $start,
        CarbonInterface $end,
        int $trainerId,
        int $roomId,
        ?int $exceptWorkoutId = null,
    ): void {
        if (Workout::notCancelled()->overlapping($start, $end, $exceptWorkoutId)->where('trainer_id', $trainerId)->exists()) {
            throw ValidationException::withMessages([
                'trainer_id' => 'У выбранного тренера уже есть тренировка в это время. '
                    .'Выберите другой интервал.',
            ]);
        }

        if (Workout::notCancelled()->overlapping($start, $end, $exceptWorkoutId)->where('room_id', $roomId)->exists()) {
            throw ValidationException::withMessages([
                'room_id' => 'Выбранный зал уже занят в это время. '
                    .'Выберите другой зал или интервал.',
            ]);
        }
    }
}
