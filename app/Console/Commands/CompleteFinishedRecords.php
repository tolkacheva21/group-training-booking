<?php

namespace App\Console\Commands;

use App\Models\Record;
use App\Models\Status;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('records:complete-finished')]
#[Description('Mark confirmed records of finished workouts as completed')]
class CompleteFinishedRecords extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $completed = Record::where('status_id', Status::idOf(Status::Confirmed))
            ->whereHas('workout', fn ($query) => $query->where('workout_end', '<=', now()))
            ->update(['status_id' => Status::idOf(Status::Completed)]);

        $this->info("Completed records: {$completed}");

        return self::SUCCESS;
    }
}
