<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'cinema_name' => 'CineBook Grand',
            'support_email' => 'support@cinebook.test',
            'timezone' => 'America/New_York',
            'seat_hold_minutes' => '10',
            'max_seats_per_booking' => '6',
            'sales_close_minutes' => '15',
            'customer_cancel_hours' => '2',
        ] as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'updated_by' => null],
            );
        }
    }
}
