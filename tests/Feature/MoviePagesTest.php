<?php

it('shows the home page and movie catalog', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('The Last Horizon')
        ->assertSee('src="'.asset('images/movies/the-last-horizon-cover.jpg').'" alt="The Last Horizon cover"', false)
        ->assertSee('Now showing');

    $this->get('/movies')
        ->assertOk()
        ->assertSee('Find your next escape')
        ->assertSee('Search movies')
        ->assertSee('Night Circuit');
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
