<?php

namespace App\Actions\Admin;

use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSettingsAction
{
    private const TYPES = [
        'cinema_name' => 'string',
        'support_email' => 'email',
        'timezone' => 'timezone',
        'currency' => 'currency',
        'refund_percent' => 'refund_percent',
        'seat_hold_minutes' => 'hold_minutes',
        'max_seats_per_booking' => 'max_seats',
        'sales_close_minutes' => 'sales_close_minutes',
        'customer_cancel_hours' => 'cancel_hours',
    ];

    public function __construct(
        private AuditLogger $audit,
        private SettingsService $settings,
    ) {}

    /** @param array<string, mixed> $values */
    public function execute(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                if (! isset(self::TYPES[$key])) {
                    throw ValidationException::withMessages(["values.{$key}" => 'This setting cannot be changed.']);
                }
                $value = $this->validateValue($key, $value);
                $setting = Setting::query()->lockForUpdate()->find($key);
                $before = $setting ? $this->audit->snapshot($setting) : null;
                $setting ??= new Setting(['key' => $key]);
                $setting->forceFill([
                    'value' => (string) $value,
                    'updated_by' => auth()->id(),
                ])->save();
                $this->audit->log('setting.updated', $setting, $before, $this->audit->snapshot($setting));
            }
        });
        $this->settings->forgetCache();
    }

    private function validateValue(string $key, mixed $value): string
    {
        $value = trim((string) $value);
        $valid = match (self::TYPES[$key]) {
            'string' => $value !== '' && mb_strlen($value) <= ($key === 'cinema_name' ? 120 : 255),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($value) <= 255,
            'timezone' => in_array($value, timezone_identifiers_list(), true),
            'currency' => $value === 'USD',
            'refund_percent' => $value === '100',
            'hold_minutes' => $value === '10',
            'max_seats' => $value === '6',
            'sales_close_minutes' => $value === '15',
            'cancel_hours' => $value === '2',
        };
        if (! $valid) {
            throw ValidationException::withMessages(["values.{$key}" => 'The value is not valid for this setting.']);
        }

        return $value;
    }
}
