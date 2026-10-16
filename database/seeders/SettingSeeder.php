<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'hold_minutes' => config('cinebook.hold_minutes'),
            'max_seats_per_booking' => config('cinebook.max_seats_per_booking'),
            'cancel_window_hours' => config('cinebook.cancel_window_hours'),
            'premium_surcharge_cents' => config('cinebook.premium_surcharge_cents'),
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }
    }
}
