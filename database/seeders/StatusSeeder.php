<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            Status::Pending => 'Не подтверждена',
            Status::Confirmed => 'Подтверждена',
            Status::Completed => 'Завершена',
            Status::Rejected => 'Отклонена',
        ];

        foreach ($statuses as $name => $title) {
            DB::table('statuses')->updateOrInsert(['name' => $name], ['title' => $title]);
        }
    }
}
