<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Test Staff',
                'password' => 'password',
                'role' => Role::Staff,
                'email_verified_at' => now(),
            ],
        );
    }
}
