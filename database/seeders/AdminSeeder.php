<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Create the default administrator account.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Администратор',
                'phone_number' => '80000000000',
                'password' => '12345678',
                'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            ]
        );
    }
}
