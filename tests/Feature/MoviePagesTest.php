<?php

use App\Enums\MovieStatus;
use App\Models\Genre;
use App\Models\Movie;
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

    $this->get(route('movies.show', $movie))
        ->assertOk()
        ->assertSee('Database Feature')
        ->assertSee('Choose your showtime')
        ->assertSee($showtime->screen->name)
        ->assertSee('7:30 PM')
        ->assertSee('data-day-button="'.$showtime->starts_at->toDateString().'"', false);

    $this->get(route('showtimes.show', $showtime))
        ->assertOk()
        ->assertSee('Database Feature')
        ->assertSee('Your selection');

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
