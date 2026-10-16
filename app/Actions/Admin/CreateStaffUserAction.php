<?php

namespace App\Actions\Admin;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateStaffUserAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password): User {
            if (User::query()->where('email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'An account with this email already exists.']);
            }
            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => Role::Staff,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $this->audit->log('staff.created', $user, null, $this->audit->snapshot($user));

            return $user;
        });
    }
}
