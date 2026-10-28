<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(SettingsService $settingsService): View
    {
        $defaults = [
            'hold_minutes' => $settingsService->get('hold_minutes'),
            'max_seats_per_booking' => $settingsService->get('max_seats_per_booking'),
            'cancel_window_hours' => $settingsService->get('cancel_window_hours'),
            'premium_surcharge_cents' => $settingsService->get('premium_surcharge_cents'),
        ];

        return view('admin.settings.edit', [
            'settings' => array_merge($defaults, [
                'premium_surcharge_cents' => ((int) $defaults['premium_surcharge_cents']) / 100,
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
