<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(Request $request, SettingsService $settings): View
    {
        $values = array_merge([
            'cinema_name' => config('app.name', 'CineBook'),
            'support_email' => config('mail.from.address', ''),
            'timezone' => config('app.timezone', 'UTC'),
            'currency' => 'USD',
            'seat_hold_minutes' => '10',
            'max_seats_per_booking' => '6',
            'sales_close_minutes' => '15',
            'customer_cancel_hours' => '2',
            'refund_percent' => '100',
        ], $settings->all());
        $stripeSecret = (string) config('services.stripe.secret', '');
        $stripeKey = (string) config('services.stripe.key', '');
        $mask = fn (string $secret): string => $secret === '' ? 'Not configured' : '••••••••'.substr($secret, -4);

        return view('admin.settings.edit', [
            'values' => $values,
            'tab' => $request->query('tab', 'general'),
            'payment' => [
                'mode' => str_starts_with($stripeSecret, 'sk_live_') ? 'Live' : ($stripeSecret !== '' ? 'Test' : 'Not configured'),
                'secret' => $mask($stripeSecret),
                'publishable' => $mask($stripeKey),
                'webhook' => $mask((string) config('services.stripe.webhook_secret', '')),
            ],
            'email' => [
                'from_name' => config('mail.from.name'),
                'from_address' => config('mail.from.address'),
                'queue_connection' => config('queue.default'),
                'retry_after' => config('queue.connections.'.config('queue.default').'.retry_after'),
            ],
            'updatedBy' => Setting::query()->with('updater')->whereIn('key', ['cinema_name', 'timezone'])->latest('updated_at')->first()?->updater?->name,
        ]);
    }
}
