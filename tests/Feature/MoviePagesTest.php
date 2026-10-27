<?php

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Support\Carbon;

it('shows the home page and movie catalog', function () {
    $this->seed();

    $this->get('/')
        ->assertOk()
        ->assertSee('The Last Horizon')
        ->assertSee('Now showing');

    $this->get('/movies')
        ->assertOk()
        ->assertSee('Find your next escape')
        ->assertSee('Search movies')
        ->assertSee('Night Circuit');
});

it('shows each movie latest upcoming showtime and its date', function () {
    $this->travelTo(Carbon::parse('2026-10-21 12:00:00'));

    $todayMovie = Movie::factory()->nowShowing()->create([
        'title' => 'Today Movie',
    ]);

    Showtime::factory()->for($todayMovie)->create([
        'starts_at' => '2026-10-21 17:30:00',
        'ends_at' => '2026-10-21 19:30:00',
    ]);

    Showtime::factory()->for($todayMovie)->create([
        'starts_at' => '2026-10-21 19:30:00',
        'ends_at' => '2026-10-21 21:30:00',
    ]);

    Showtime::factory()->for($todayMovie)->create([
        'starts_at' => '2026-10-22 10:00:00',
        'ends_at' => '2026-10-22 12:00:00',
    ]);

    $tomorrowMovie = Movie::factory()->nowShowing()->create([
        'title' => 'Tomorrow Movie',
    ]);

    Showtime::factory()->for($tomorrowMovie)->create([
        'starts_at' => '2026-10-22 08:00:00',
        'ends_at' => '2026-10-22 10:00:00',
    ]);

    $laterMovie = Movie::factory()->nowShowing()->create([
        'title' => 'Later Movie',
    ]);

    Showtime::factory()->for($laterMovie)->create([
        'starts_at' => '2026-10-25 15:00:00',
        'ends_at' => '2026-10-25 17:47:00',
    ]);

    $farMovie = Movie::factory()->nowShowing()->create([
        'title' => 'Far Movie',
    ]);

    Showtime::factory()->for($farMovie)->create([
        'starts_at' => '2026-11-05 15:00:00',
        'ends_at' => '2026-11-05 17:47:00',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Tomorrow · 10:00')
        ->assertSee('Tomorrow · 08:00')
        ->assertDontSee('Today · 17:30')
        ->assertDontSee('Today · 19:30')
        ->assertDontSee('Showtimes available soon')
        ->assertSee('Oct 25 · 15:00')
        ->assertSee('Nov 5 · 15:00');

    $this->get('/movies')
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

    $firstShowtime = Showtime::factory()->for($firstMovie)->for($screen)->create([
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
        ->assertSee(route('showtimes.show', $firstShowtime))
        ->assertDontSee('20:30')
        ->assertDontSee('22:00');

    $response->assertViewHas('showtimesByDate', function ($showtimesByDate) use ($firstMovie, $secondMovie) {
        return $showtimesByDate
            ->get('2026-10-23')
            ->pluck('movie_id')
            ->all() === [$firstMovie->id, $secondMovie->id];
    });
});

it('builds the home schedule from the next dates that actually have bookable showtimes', function () {
    $this->travelTo(Carbon::parse('2026-10-27 12:00:00', 'Asia/Karachi'));

    $cinema = Cinema::factory()->create(['name' => 'March Cinema']);
    $screen = Screen::factory()->for($cinema)->create();
    $movie = Movie::factory()->nowShowing()->create([
        'title' => 'March Schedule Movie',
        'slug' => 'march-schedule-movie',
    ]);

    $showtime = Showtime::factory()->for($movie)->for($screen)->create([
        'starts_at' => '2027-03-03 17:30:00',
        'ends_at' => '2027-03-03 19:30:00',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Wed')
        ->assertSee('Mar 3')
        ->assertSee('March Schedule Movie')
        ->assertSee('17:30')
        ->assertSee(route('showtimes.show', $showtime))
        ->assertDontSee('No bookable showtimes for this date.');
});

it('shows movie details and rejects unknown movie slugs', function () {
    $this->seed();

    $this->get('/movies/the-last-horizon')
        ->assertOk()
        ->assertSee('Choose your showtime');

    $this->get('/movies/not-a-movie')
        ->assertNotFound();
});

it('only shows a seat map for movies with a configured seat map', function () {
    $this->get('/movies/the-last-horizon/seats?date=2026-10-15&time=19:30')
        ->assertOk()
        ->assertSee('Your selection');

    $this->get('/movies/night-circuit/seats')
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
