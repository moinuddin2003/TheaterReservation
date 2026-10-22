<?php

use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Support\Carbon;

it('shows the home page and movie catalog', function () {
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

it('shows movie details and rejects unknown movie slugs', function () {
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
