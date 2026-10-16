<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /** @return array<string, string> */
    public function all(): array
    {
        return Cache::remember('cinebook.settings', now()->addHour(), fn () => Setting::query()->pluck('value', 'key')->all());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public function forgetCache(): void
    {
        Cache::forget('cinebook.settings');
    }
}
