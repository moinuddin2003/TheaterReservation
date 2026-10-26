<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            AdminSeeder::class,
            GenreSeeder::class,
            CinemaSeeder::class,
            MovieSeeder::class,
            ShowtimeSeeder::class,
            PaymentSeeder::class,
            TicketDemoSeeder::class,
        ]);
    }
}
