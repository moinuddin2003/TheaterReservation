<?php

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;

it('shows database movies on the home page and public catalog', function () {
    $genre = Genre::factory()->create([
        'name' => 'Drama',
        'slug' => 'drama',
    ]);
    $nowShowing = Movie::factory()->create([
        'title' => 'Database Feature',
        'slug' => 'database-feature',
        'status' => MovieStatus::NowShowing,
    ]);
    $nowShowing->genres()->attach($genre);

    Movie::factory()->comingSoon()->create([
        'title' => 'Database Coming Soon',
        'slug' => 'database-coming-soon',
    ]);

    Showtime::factory()->for($nowShowing)->create([
        'starts_at' => now()->addDay()->setTime(19, 30),
        'ends_at' => now()->addDay()->setTime(21, 30),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Database Feature')
        ->assertSee('Tomorrow · 19:30')
        ->assertSee('Database Coming Soon')
        ->assertDontSee('The Last Horizon');

    $this->get('/movies')
        ->assertOk()
        ->assertSee('Find your next escape')
        ->assertSee('Search movies')
        ->assertSee('Database Feature')
        ->assertSee('Database Coming Soon')
        ->assertDontSee('Night Circuit');

    $this->get('/movies?search=Database%20Feature')
        ->assertOk()
        ->assertSee('Database Feature')
        ->assertDontSee('Database Coming Soon');

    $this->get('/movies?genre=drama&status=now_showing')
        ->assertOk()
        ->assertSee('Database Feature')
        ->assertDontSee('Database Coming Soon');

    $this->get('/movies?status=coming_soon')
        ->assertOk()
        ->assertSee('Database Coming Soon')
        ->assertDontSee('Database Feature');
});

it('shows database movie details and database showtimes', function () {
    $movie = Movie::factory()->create([
        'title' => 'Database Feature',
        'slug' => 'database-feature',
    ]);
    $showtime = Showtime::factory()->for($movie)->create([
        'starts_at' => now()->addDay()->setTime(19, 30),
        'ends_at' => now()->addDay()->setTime(21, 30),
    ]);
    Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'A',
        'seat_number' => 1,
        'label' => 'A1',
    ]);

    $this->get(route('movies.show', $movie))
        ->assertOk()
        ->assertSee('Database Feature')
        ->assertSee('Choose your showtime')
        ->assertSee($showtime->screen->name)
        ->assertSee('7:30 PM')
        ->assertSee('data-day-button="'.$showtime->starts_at->toDateString().'"', false);

    $this->get(route('showtimes.show', $showtime))
        ->assertOk()
        ->assertSee('Tomorrow · 10:00')
        ->assertSee('Tomorrow · 08:00')
        ->assertDontSee('Today · 17:30')
        ->assertDontSee('Today · 19:30')
        ->assertDontSee('Showtimes available soon')
        ->assertSee('Oct 25 · 15:00')
        ->assertSee('Nov 5 · 15:00');
});

it('shows bookable showtimes for every now showing movie at the cinema', function () {
    $this->travelTo(Carbon::parse('2026-10-22 12:00:00'));

    $cinema = Cinema::factory()->create(['name' => 'Test Cinema']);
    $screen = Screen::factory()->for($cinema)->create(['name' => 'Screen A']);
    $otherCinema = Cinema::factory()->create();
    $otherScreen = Screen::factory()->for($otherCinema)->create();

    $firstMovie = Movie::factory()->nowShowing()->create([
        'title' => 'First Schedule Movie',
        'slug' => 'first-schedule-movie',
    ]);
    $secondMovie = Movie::factory()->nowShowing()->create([
        'title' => 'Second Schedule Movie',
        'slug' => 'second-schedule-movie',
    ]);
    $comingSoonMovie = Movie::factory()->comingSoon()->create();
    $otherCinemaMovie = Movie::factory()->nowShowing()->create();

    Showtime::factory()->for($firstMovie)->for($screen)->create([
        'starts_at' => '2026-10-23 17:30:00',
        'ends_at' => '2026-10-23 19:30:00',
    ]);
    Showtime::factory()->for($secondMovie)->for($screen)->create([
        'starts_at' => '2026-10-23 19:30:00',
        'ends_at' => '2026-10-23 21:30:00',
    ]);
    Showtime::factory()->for($comingSoonMovie)->for($screen)->create([
        'starts_at' => '2026-10-23 20:30:00',
        'ends_at' => '2026-10-23 22:30:00',
    ]);
    Showtime::factory()->for($otherCinemaMovie)->for($otherScreen)->create([
        'starts_at' => '2026-10-23 21:30:00',
        'ends_at' => '2026-10-23 23:30:00',
    ]);
    Showtime::factory()->for($firstMovie)->for($screen)->cancelled()->create([
        'starts_at' => '2026-10-23 22:00:00',
        'ends_at' => '2026-10-24 00:00:00',
    ]);

    $response = $this->get('/')
        ->assertOk()
        ->assertSee('showtimes available')
        ->assertSee('First Schedule Movie')
        ->assertSee('Second Schedule Movie')
        ->assertSee('17:30')
        ->assertSee('19:30')
        ->assertSee('Screen A')
        ->assertDontSee('20:30')
        ->assertDontSee('22:00');

    $response->assertViewHas('showtimesByDate', function ($showtimesByDate) use ($firstMovie, $secondMovie) {
        return $showtimesByDate
            ->get('2026-10-23')
            ->pluck('movie_id')
            ->all() === [$firstMovie->id, $secondMovie->id];
    });
});

it('shows movie details and rejects unknown movie slugs', function () {
    $this->get('/movies/the-last-horizon')
        ->assertOk()
        ->assertSee('Choose your showtime');

    $this->get(route('movies.seats', [
        'movie' => $movie->slug,
        'date' => $showtime->starts_at->toDateString(),
        'time' => $showtime->starts_at->format('H:i'),
    ]))->assertRedirect(route('showtimes.show', $showtime));

    $this->get(route('movies.show', ['movie' => 'not-a-movie']))
        ->assertNotFound();
});

it('shows the customer sign-in and registration screens', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Email address');

    $this->get('/register')
        ->assertOk()
        ->assertSee('Your seat in the story')
        ->assertSee('Confirm password');

    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('Forgot your password?');

    $this->get('/reset-password/test-token')
        ->assertOk()
        ->assertSee('Reset Password');
});
