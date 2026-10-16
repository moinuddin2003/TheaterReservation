<?php

namespace Database\Seeders;

use App\Enums\ShowtimeStatus;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ShowtimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var Collection<int, Movie> $movies */
        $movies = Movie::nowShowing()->get();

        if ($movies->isEmpty()) {
            return;
        }

        $startHours = [11, 15, 19];

        foreach (Screen::all() as $screen) {
            $basePrice = $screen->name === 'IMAX' ? 1200 : 1000;

            for ($day = 1; $day <= 7; $day++) {
                foreach ($startHours as $index => $hour) {
                    $movie = $movies[($day + $index) % $movies->count()];
                    $startsAt = now()->addDays($day)->setTime($hour, 0);

                    $showtime = Showtime::firstOrNew([
                        'screen_id' => $screen->id,
                        'starts_at' => $startsAt,
                    ]);

                    $showtime->movie_id = $movie->id;
                    $showtime->ends_at = $startsAt->copy()->addMinutes($movie->duration_minutes + 15);
                    $showtime->base_price_cents = $basePrice;
                    $showtime->status = ShowtimeStatus::Scheduled;
                    $showtime->save();
                }
            }
        }
    }
}
