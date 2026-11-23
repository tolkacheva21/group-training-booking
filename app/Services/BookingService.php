<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Record;
use App\Models\Status;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Запись посетителя на тренировку.
 *
 * Правила: записываться может только пользователь с ролью "user",
 * только на доступную тренировку и при наличии свободных мест.
 */
class BookingService
{
    public function __construct(private readonly PaymentSimulator $payments) {}

    /**
     * @throws ValidationException
     */
    public function book(User $user, int $workoutId): Record
    {
        if (! $user->can('book', Record::class)) {
            throw ValidationException::withMessages([
                'workout_id' => 'Записываться на тренировки могут только посетители.',
            ]);
        }

        $record = DB::transaction(function () use ($user, $workoutId) {
            $workout = Workout::with('room')->lockForUpdate()->findOrFail($workoutId);

            $this->ensureBookable($workout);

            if ($workout->trainer_id === $user->id) {
                throw ValidationException::withMessages([
                    'workout_id' => 'Нельзя записаться на собственную тренировку.',
                ]);
            }

            $alreadyExists = Record::where('workout_id', $workout->id)
                ->where('user_id', $user->id)
                ->whereHas('status', fn ($query) => $query->whereIn('name', [Status::Pending, Status::Confirmed, Status::Completed]))
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'workout_id' => 'Вы уже записаны на выбранную тренировку.',
                ]);
            }

            if ($workout->bookedRecords()->count() >= $workout->room->capacity) {
                throw ValidationException::withMessages([
                    'workout_id' => 'На тренировке нет свободных мест.',
                ]);
            }

            // Новая запись ждёт подтверждения менеджером и место пока не занимает
            return Record::create([
                'workout_id' => $workout->id,
                'user_id' => $user->id,
                'status_id' => Status::idOf(Status::Pending),
                'payment_status' => PaymentStatus::Unpaid,
                'amount' => $workout->price,
            ]);
        });

        return $record;
    }

    /**
     * @throws ValidationException
     */
    public function ensureBookable(Workout $workout): void
    {
        if ($workout->isCancelled()) {
            throw ValidationException::withMessages([
                'workout_id' => 'Тренировка отменена, запись недоступна.',
            ]);
        }

        if ($workout->isFinished()) {
            throw ValidationException::withMessages([
                'workout_id' => 'Тренировка уже завершилась, запись недоступна.',
            ]);
        }

        if ($workout->hasStarted()) {
            throw ValidationException::withMessages([
                'workout_id' => 'Тренировка уже началась, запись недоступна.',
            ]);
        }
    }
}
