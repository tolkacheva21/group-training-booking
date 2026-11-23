<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'trainer_id',
        'room_id',
        'inventory_id',
        'inventory_quantity',
        'price',
        'workout_start',
        'workout_end',
        'cancelled_at',
    ];

    // обрабатывать как объекты дата и время
    protected $casts = [
        'workout_start' => 'datetime',
        'workout_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    // тренировка принадлежит тренеру, тренер может иметь много тренировок
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    // тренировка проводится в одном зале, зал может иметь много тренировок
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // тренировка может использовать инвентарь
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    // тренировка может иметь много записей
    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    // места в зале занимают подтверждённые и завершённые записи
    public function occupyingRecords(): HasMany
    {
        return $this->records()->whereHas(
            'status',
            fn ($query) => $query->whereIn('name', [Status::Confirmed, Status::Completed])
        );
    }

    // все записи, которые резервируют место: ожидающие, подтверждённые и завершённые
    public function bookedRecords(): HasMany
    {
        return $this->records()->whereHas(
            'status',
            fn ($query) => $query->whereIn('name', [Status::Pending, Status::Confirmed, Status::Completed])
        );
    }

    /**
     * Тренировки, которые не были отменены и потому занимают ресурсы.
     */
    public function scopeNotCancelled(Builder $query): void
    {
        $query->whereNull('cancelled_at');
    }

    /**
     * Тренировки, чей временной интервал пересекается с указанным.
     * Смежные интервалы (11:00 после 10:00–11:00) конфликтом не считаются.
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end, ?int $exceptWorkoutId = null): void
    {
        $query->where('workout_start', '<', $end)
            ->where('workout_end', '>', $start)
            ->when($exceptWorkoutId, fn (Builder $query) => $query->whereKeyNot($exceptWorkoutId));
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function hasStarted(): bool
    {
        return $this->workout_start->lessThanOrEqualTo(now());
    }

    public function isFinished(): bool
    {
        return $this->workout_end->lessThanOrEqualTo(now());
    }

    /**
     * Можно ли записаться: тренировка не отменена, ещё не началась.
     */
    public function isBookable(): bool
    {
        return ! $this->isCancelled() && ! $this->hasStarted();
    }

    /**
     * Количество свободных мест с учётом всех активных записей.
     */
    public function availableSeats(): int
    {
        $capacity = (int) ($this->room?->capacity ?? 0);
        $booked = $this->booked_count ?? $this->bookedRecords()->count();

        return max(0, $capacity - (int) $booked);
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isCancelled() => 'Отменена',
            $this->isFinished() => 'Завершена',
            $this->hasStarted() => 'Идёт',
            default => 'Запланирована',
        };
    }
}
