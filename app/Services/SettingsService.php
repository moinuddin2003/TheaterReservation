<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $key): mixed
    {
        return Setting::query()->whereKey($key)->value('value')
            ?? config('cinebook.'.$key);
    }
}
