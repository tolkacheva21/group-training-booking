<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Record;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Внутренняя имитация оплаты записи на тренировку.
 *
 * Реальные платёжные шлюзы не используются: оплата «проходит» внутри приложения,
 * а результат сохраняется в записи (payment_status, amount, paid_at).
 */
class PaymentSimulator
{
    /**
     * Имитирует успешную оплату. Повторная оплата уже оплаченной записи запрещена.
     *
     * @throws ValidationException
     */
    public function pay(Record $record): Record
    {
        return DB::transaction(function () use ($record) {
            /** @var Record $locked */
            $locked = Record::with(['workout', 'status'])->lockForUpdate()->findOrFail($record->id);

            if ($locked->isPaid()) {
                throw ValidationException::withMessages([
                    'payment' => 'Эта запись уже оплачена. Повторная оплата невозможна.',
                ]);
            }

            if ($locked->hasStatus(Status::Rejected) || $locked->hasStatus(Status::Completed)) {
                throw ValidationException::withMessages([
                    'payment' => 'Нельзя оплатить отклонённую или завершённую запись.',
                ]);
            }

            if ($locked->workout->isCancelled()) {
                throw ValidationException::withMessages([
                    'payment' => 'Тренировка отменена, оплата недоступна.',
                ]);
            }

            if ($locked->workout->isFinished()) {
                throw ValidationException::withMessages([
                    'payment' => 'Тренировка уже завершилась, оплата недоступна.',
                ]);
            }

            $locked->update([
                'payment_status' => PaymentStatus::Paid,
                'amount' => $locked->workout->price,
                'paid_at' => now(),
            ]);

            return $locked;
        });
    }

    /**
     * Имитирует отмену/неуспешное завершение оплаты. Оплаченную запись так отменить нельзя.
     *
     * @throws ValidationException
     */
    public function cancel(Record $record): Record
    {
        return DB::transaction(function () use ($record) {
            /** @var Record $locked */
            $locked = Record::lockForUpdate()->findOrFail($record->id);

            if ($locked->isPaid()) {
                throw ValidationException::withMessages([
                    'payment' => 'Оплата уже прошла, отменить её нельзя. Отмените саму запись.',
                ]);
            }

            $locked->update([
                'payment_status' => PaymentStatus::Cancelled,
                'paid_at' => null,
            ]);

            return $locked;
        });
    }
}
