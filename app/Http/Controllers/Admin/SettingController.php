<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $defaults = [
            'hold_minutes' => config('cinebook.hold_minutes', 10),
            'max_seats_per_booking' => config('cinebook.max_seats_per_booking', 6),
            'cancel_window_hours' => config('cinebook.cancel_window_hours', 2),
            'premium_surcharge_cents' => config('cinebook.premium_surcharge_cents', 200),
        ];

        $settings = Setting::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->all();

        return view('admin.settings.edit', [
            'settings' => array_merge($defaults, $settings, [
                'premium_surcharge_cents' => ((int) ($settings['premium_surcharge_cents'] ?? $defaults['premium_surcharge_cents'])) / 100,
            ]),
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $values = $request->validate([
            'hold_minutes' => ['required', 'integer', 'between:2,30'],
            'max_seats_per_booking' => ['required', 'integer', 'between:1,10'],
            'cancel_window_hours' => ['required', 'integer', 'between:0,72'],
            'premium_surcharge_cents' => ['required', 'numeric', 'between:0,50'],
        ]);

        $values['premium_surcharge_cents'] = (string) round((float) $values['premium_surcharge_cents'] * 100);

        foreach ($values as $key => $value) {
            $setting = Setting::query()->firstOrNew(['key' => $key]);
            $before = ['key' => $key, 'value' => $setting->value];
            $setting->value = (string) $value;
            $setting->updated_by = $request->user()->id;
            $setting->save();
            $auditLogger->log(
                $request->user(),
                'settings.updated',
                $setting,
                $before,
                ['key' => $key, 'value' => (string) $value],
                $request->ip(),
            );
        }

        return to_route('admin.settings.edit')->with('status', 'Settings saved.');
    }
}
