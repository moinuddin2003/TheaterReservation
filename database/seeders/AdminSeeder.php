<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (filled($email) && filled($password)) {
            User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'CineBook Administrator',
                    'password' => $password,
                    'role' => Role::Admin,
                    'email_verified_at' => now(),
                ],
            );

            return;
        }

        if (User::query()->where('role', Role::Admin)->exists()) {
            return;
        }

        $this->command?->warn('No administrator exists. Set ADMIN_EMAIL and ADMIN_PASSWORD in your .env file to create one.');
    }
}
