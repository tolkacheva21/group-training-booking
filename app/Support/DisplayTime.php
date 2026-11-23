<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Преобразование даты и времени между часовым поясом хранения (UTC)
 * и часовым поясом отображения/ввода (config('app.display_timezone')).
 */
class DisplayTime
{
    public static function timezone(): string
    {
        return (string) config('app.display_timezone', config('app.timezone', 'UTC'));
    }

    /**
     * Разбирает введённое пользователем локальное время и возвращает момент в UTC.
     */
    public static function toUtc(CarbonInterface|string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, self::timezone())->utc();
    }

    /**
     * Переводит сохранённый момент в часовой пояс отображения.
     */
    public static function toLocal(CarbonInterface $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value)->setTimezone(self::timezone());
    }
}
