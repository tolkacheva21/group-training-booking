<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'quantity'];

    // Инвентарь может быть выдан на несколько тренировок
    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }

    /**
     * Количество, занятое тренировками, которые пересекаются с указанным периодом.
     * Отменённые тренировки и завершившиеся ранее не учитываются — инвентарь возвращается.
     */
    public function occupiedBetween(CarbonInterface $start, CarbonInterface $end, ?int $exceptWorkoutId = null): int
    {
        return (int) $this->workouts()
            ->notCancelled()
            ->where('workout_start', '<', $end)
            ->where('workout_end', '>', $start)
            ->when($exceptWorkoutId, fn ($query) => $query->whereKeyNot($exceptWorkoutId))
            ->sum('inventory_quantity');
    }

    /**
     * Количество, свободное в указанный период.
     */
    public function availableBetween(CarbonInterface $start, CarbonInterface $end, ?int $exceptWorkoutId = null): int
    {
        return max(0, $this->quantity - $this->occupiedBetween($start, $end, $exceptWorkoutId));
    }

    /**
     * Количество, занятое тренировками, которые идут прямо сейчас.
     */
    public function occupiedNow(): int
    {
        return $this->occupiedBetween(now(), now());
    }

    /**
     * Количество, свободное прямо сейчас.
     */
    public function availableNow(): int
    {
        return max(0, $this->quantity - $this->occupiedNow());
    }

    /**
     * Предстоящие и текущие тренировки, которые резервируют инвентарь.
     * Отменённые и завершившиеся тренировки сюда не попадают.
     *
     * @return Collection<int, Workout>
     */
    public function upcomingWorkouts(): Collection
    {
        if ($this->relationLoaded('workouts')) {
            return $this->workouts
                ->filter(fn (Workout $workout) => ! $workout->isCancelled()
                    && $workout->workout_end->greaterThan(now())
                    && (int) $workout->inventory_quantity > 0)
                ->sortBy('workout_start')
                ->values();
        }

        return $this->workouts()
            ->notCancelled()
            ->where('workout_end', '>', now())
            ->where('inventory_quantity', '>', 0)
            ->orderBy('workout_start')
            ->get();
    }
}
