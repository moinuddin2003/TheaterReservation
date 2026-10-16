<?php

namespace App\Actions\Admin;

use App\Enums\Role;
use App\Models\User;
use App\Services\Admin\UserActivity;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetUserActiveAction
{
    public function __construct(
        private UserActivity $activity,
        private AuditLogger $audit,
    ) {}

    public function execute(User $user, bool $active, User $actor): User
    {
        return DB::transaction(function () use ($user, $active, $actor): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($user->is($actor) && ! $active) {
                throw ValidationException::withMessages(['user' => 'You cannot deactivate your own account.']);
            }
            if ($user->role === Role::Admin) {
                throw ValidationException::withMessages(['user' => 'Administrator accounts cannot be deactivated here.']);
            }

            $before = ['active' => $this->activity->isActive($user)];
            $this->activity->setActive($user, $active);
            $this->audit->log('user.'.($active ? 'activated' : 'deactivated'), $user, $before, ['active' => $active]);

            return $user;
        });
    }
}
