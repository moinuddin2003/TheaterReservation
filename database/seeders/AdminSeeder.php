<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'password');

        $this->user('CineBook Admin', 'admin@cinebook.test', Role::Admin, $password);
        $this->user('CineBook Staff', 'staff@cinebook.test', Role::Staff, $password);
        $this->user('Demo Customer', 'customer@cinebook.test', Role::Customer, $password);
    }

    /**
     * Create or update a verified user with the given role.
     */
    private function user(string $name, string $email, Role $role, string $password): void
    {
        $user = User::firstOrNew(['email' => $email]);

        $user->name = $name;
        $user->role = $role;
        $user->password = $password;
        $user->email_verified_at = now();
        $user->save();
    }
}
