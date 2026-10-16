<?php

namespace App\Services\Admin;

use App\Models\Setting;
use App\Models\User;

class UserActivity
{
    public function isActive(User $user): bool
    {
        return Setting::query()->whereKey($this->settingKey($user))->value('value') !== '0';
    }

    public function setActive(User $user, bool $active): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $this->settingKey($user)],
            ['value' => $active ? '1' : '0', 'updated_by' => auth()->id()],
        );
    }

    public function inactiveUserIds(): array
    {
        return Setting::query()
            ->where('key', 'like', 'user_active_%')
            ->where('value', '0')
            ->pluck('key')
            ->map(fn (string $key): int => (int) substr($key, strlen('user_active_')))
            ->all();
    }

    private function settingKey(User $user): string
    {
        return 'user_active_'.$user->getKey();
    }
}
