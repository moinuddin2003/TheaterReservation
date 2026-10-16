<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if (app()->isProduction() && (! $email || ! $password)) {
            throw new RuntimeException('ADMIN_EMAIL and ADMIN_PASSWORD must be set before seeding an administrator in production.');
        }

        $email ??= 'admin@example.com';
        $password ??= 'password';
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => 'CineBook Administrator',
            'password' => $password,
            'role' => Role::Admin,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
    }
}
