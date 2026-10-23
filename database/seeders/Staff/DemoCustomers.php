<?php

namespace Database\Seeders\Staff;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The demo customers behind the staff demo bookings (names from the Figma screens).
 */
class DemoCustomers
{
    /** @var list<string> */
    public const NAMES = [
        'Alex Morgan', 'Maya Chen', 'Noah Patel', 'Ella Brooks', 'Liam Park', 'Olivia Reed',
        'Ava Clark', 'Ethan Ross', 'Isabella Moore', 'James Cooper', 'Sophia Bennett', 'Lucas Hayes',
    ];

    /**
     * Creates or updates every demo customer (verified, same password as the demo users).
     *
     * @return Collection<string, User> keyed by name
     */
    public function seed(): Collection
    {
        $password = env('SEED_PASSWORD', 'password');

        return collect(self::NAMES)->mapWithKeys(function (string $name) use ($password): array {
            $user = User::firstOrNew(['email' => $this->email($name)]);

            $user->name = $name;
            $user->role = Role::Customer;
            $user->password = $password;
            $user->email_verified_at ??= now();
            $user->save();

            return [$name => $user];
        });
    }

    private function email(string $name): string
    {
        return str_replace(' ', '.', strtolower($name)).'@cinebook.test';
    }
}
