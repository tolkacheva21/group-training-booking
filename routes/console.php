<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Завершение записей после окончания тренировки.
// withoutOverlapping не даёт запустить задачу повторно, если предыдущий запуск ещё идёт.
Schedule::command('records:complete-finished')
    ->everyMinute()
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
