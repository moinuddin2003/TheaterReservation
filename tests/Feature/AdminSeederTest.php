<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->adminSeederOriginalEnvironment = [];

    foreach (['ADMIN_EMAIL', 'ADMIN_PASSWORD'] as $key) {
        $this->adminSeederOriginalEnvironment[$key] = [
            'process' => getenv($key),
            'env' => $_ENV[$key] ?? null,
            'env_present' => array_key_exists($key, $_ENV),
            'server' => $_SERVER[$key] ?? null,
            'server_present' => array_key_exists($key, $_SERVER),
        ];

        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
});

afterEach(function () {
    foreach ($this->adminSeederOriginalEnvironment as $key => $values) {
        if ($values['process'] === false) {
            putenv($key);
        } else {
            putenv($key.'='.$values['process']);
        }

        if ($values['env_present']) {
            $_ENV[$key] = $values['env'];
        } else {
            unset($_ENV[$key]);
        }

        if ($values['server_present']) {
            $_SERVER[$key] = $values['server'];
        } else {
            unset($_SERVER[$key]);
        }
    }
});

function setAdminSeederCredentials(string $email, string $password): void
{
    foreach (['ADMIN_EMAIL' => $email, 'ADMIN_PASSWORD' => $password] as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

test('it silently keeps an existing admin unchanged when credentials are unset', function () {
    $admin = User::factory()->create([
        'email' => 'existing-admin@example.com',
        'password' => 'existing-password',
        'role' => Role::Admin,
    ]);
    $passwordHash = $admin->password;

    $this->artisan('db:seed', ['--class' => AdminSeeder::class])
        ->doesntExpectOutputToContain('No administrator exists')
        ->assertExitCode(0);

    expect(User::query()->where('email', $admin->email)->count())->toBe(1)
        ->and($admin->fresh()->role)->toBe(Role::Admin)
        ->and($admin->fresh()->password)->toBe($passwordHash);
});

test('it warns and continues when credentials are unset and no admin exists', function () {
    $this->artisan('db:seed', ['--class' => AdminSeeder::class])
        ->expectsOutputToContain('Set ADMIN_EMAIL and ADMIN_PASSWORD in your .env file')
        ->assertExitCode(0);

    expect(User::query()->where('role', Role::Admin)->exists())->toBeFalse();
});

test('it creates an admin from configured credentials when the email is unused', function () {
    setAdminSeederCredentials('new-admin@example.com', 'configured-password');

    $this->artisan('db:seed', ['--class' => AdminSeeder::class])
        ->assertExitCode(0);

    $admin = User::query()->where('email', 'new-admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe(Role::Admin)
        ->and(Hash::check('configured-password', $admin->password))->toBeTrue();
});

test('it never changes an existing admin when configured credentials use its email', function () {
    setAdminSeederCredentials('existing-admin@example.com', 'new-password');

    $admin = User::factory()->create([
        'email' => 'existing-admin@example.com',
        'password' => 'existing-password',
        'role' => Role::Admin,
    ]);
    $passwordHash = $admin->password;

    $this->artisan('db:seed', ['--class' => AdminSeeder::class])->assertExitCode(0);
    $this->artisan('db:seed', ['--class' => AdminSeeder::class])->assertExitCode(0);

    expect(User::query()->where('email', $admin->email)->count())->toBe(1)
        ->and($admin->fresh()->role)->toBe(Role::Admin)
        ->and($admin->fresh()->password)->toBe($passwordHash);
});
