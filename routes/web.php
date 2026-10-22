<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShowtimeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

Route::post('/stripe/webhook', function (Request $request) {
    $signature = $request->header('Stripe-Signature');
    $secret = config('services.stripe.webhook_secret');

    if (! $signature || ! $secret) {
        return response()->json(['error' => 'Webhook configuration missing'], 400);
    }

    try {
        // consturctEvent SignatureVerify Krta he
        $event = Webhook::constructEvent(
            $request->getContent(),
            $signature,
            $secret,
        );
    } catch (UnexpectedValueException|SignatureVerificationException $exception) {
        return response()->json(['error' => 'Invalid webhook'], 400);
    }

    Log::info('Verified Stripe webhook: '.$event->type);

    return response()->json(['received' => true]);
});

Route::get('/', function () {
    $movies = config('cinema.movies');
    $featuredMovie = $movies[config('cinema.featured_movie')];

    return view('home', [
        'featuredMovie' => $featuredMovie,
        'nowShowing' => collect(config('cinema.catalog.now_showing'))->map(fn ($slug) => $movies[$slug]),
        'comingSoon' => collect(config('cinema.catalog.coming_soon'))->map(fn ($slug) => $movies[$slug]),
        'dates' => config('cinema.home.dates'),
        'genres' => config('cinema.home.genres'),
        'featuredShowtime' => config('cinema.home.featured_showtime'),
    ]);
})->name('home');

Route::get('/movies', function () {
    $movies = config('cinema.movies');

    return view('movies.catalog', [
        'nowShowing' => collect(config('cinema.catalog.now_showing'))->map(fn ($slug) => $movies[$slug]),
        'comingSoon' => collect(config('cinema.catalog.coming_soon'))->map(fn ($slug) => $movies[$slug]),
        'genres' => config('cinema.home.genres'),
    ]);
})->name('movies.index');

Route::get('/movies/{movieSlug}', function (string $movieSlug) {
    $movie = config('cinema.movies.'.$movieSlug);

    abort_if(! is_array($movie), 404);

    return view('movies.index', ['movie' => $movie]);
})->name('movies.show');

Route::get('/movies/{movieSlug}/seats', function (string $movieSlug) {
    $movie = config('cinema.movies.'.$movieSlug);

    abort_if(! is_array($movie) || empty($movie['seat_map']), 404);

    return view('movies.seats', ['movie' => $movie]);
})->name('movies.seats');

Route::get('/showtimes/{showtime}', [ShowtimeController::class, 'show'])->name('showtimes.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{reference}/checkout', [BookingController::class, 'checkout'])->name('bookings.checkout');
    Route::get('/bookings/{reference}/processing', [BookingController::class, 'processing'])->name('bookings.processing');
    Route::get('/bookings/{reference}/confirmed', [BookingController::class, 'confirmed'])->name('bookings.confirmed');
    Route::get('/bookings/{reference}', [BookingController::class, 'show'])->name('bookings.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
