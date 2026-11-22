<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'quantity'];

    // Инвентарь может быть выдан на несколько тренировок
    public function workouts()
    {
        return $this->hasMany(Workout::class);
    }

    /**
     * Количество, занятое тренировками, которые пересекаются с указанным периодом.
     * После окончания тренировки её инвентарь больше не учитывается, то есть возвращается.
     */
    public function occupiedBetween(CarbonInterface $start, CarbonInterface $end, ?int $exceptWorkoutId = null): int
    {
        return (int) $this->workouts()
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
}
