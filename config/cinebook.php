<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    |
    | Amounts are always stored as integer cents (USD). The currency code is
    | the three letter ISO code used by the payment gateway and the schema.
    |
    */

    'currency' => env('CINEBOOK_CURRENCY', 'usd'),
    'currency_symbol' => '$',

    /*
    |--------------------------------------------------------------------------
    | Booking rules (defaults)
    |--------------------------------------------------------------------------
    |
    | These mirror the editable values in the settings table and act as the
    | fallback for SettingsService when a key is missing.
    |
    */

    'hold_minutes' => 10,
    'max_seats_per_booking' => 6,
    'cancel_window_hours' => 2,
    'premium_surcharge_cents' => 200,

    /*
    |--------------------------------------------------------------------------
    | Fixed constants
    |--------------------------------------------------------------------------
    |
    | Sales close a fixed number of minutes before the showtime starts. A
    | ticket may only be scanned between these windows.
    |
    */

    'sales_cutoff_minutes' => 15,
    'scan_window_before_minutes' => 60,
    'scan_window_after_minutes' => 30,

    /*
    |--------------------------------------------------------------------------
    | Demo data
    |--------------------------------------------------------------------------
    |
    | Placeholder trailer used by the movie factory and seeder. Real trailers
    | can be dropped into database/seeders/data/movies.php at any time.
    |
    */

    'sample_trailer_url' => env(
        'CINEBOOK_SAMPLE_TRAILER_URL',
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
    ),

];
