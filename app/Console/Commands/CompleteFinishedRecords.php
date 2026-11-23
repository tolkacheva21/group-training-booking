<?php

namespace App\Console\Commands;

use App\Models\Record;
use App\Models\Status;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('records:complete-finished')]
#[Description('Mark confirmed records of finished workouts as completed')]
class CompleteFinishedRecords extends Command
{
    /**
     * Execute the console command.
     *
     * Команда идемпотентна: повторный запуск не меняет уже завершённые записи,
     * а также «догоняет» тренировки, которые закончились, пока планировщик был выключен.
     */
    public function handle(): int
    {
        $completed = Record::query()
            ->where('status_id', Status::idOf(Status::Confirmed))
            ->whereHas('workout', fn ($query) => $query->where('workout_end', '<=', now()))
            ->update(['status_id' => Status::idOf(Status::Completed)]);

        // Синхронизируем счётчик подтверждённых участников у прошедших тренировок
        DB::table('workouts')
            ->where('workout_end', '<=', now())
            ->update([
                'current_participants' => DB::raw(
                    '(select count(*) from records '
                    .'inner join statuses on statuses.id = records.status_id '
                    ."where records.workout_id = workouts.id and statuses.name in ('confirmed', 'completed'))"
                ),
            ]);

        $this->info("Completed records: {$completed}");

        return self::SUCCESS;
    }
}
