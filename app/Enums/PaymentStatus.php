<?php

namespace App\Enums;

/**
 * Статус оплаты записи на тренировку.
 *
 * Оплата имитируется внутри приложения: реальные платёжные шлюзы не используются.
 */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * Понятная пользователю подпись статуса.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Не оплачено',
            self::Paid => 'Оплачено',
            self::Cancelled => 'Оплата отменена',
        };
    }
}
