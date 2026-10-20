<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::post('/stripe/webhook', function (Request $request) {
    $signature = $request->header('Stripe-Signature');
    $secret = config('services.stripe.webhook_secret');

    if (! $signature || ! $secret) {
        return response()->json(['error' => 'Webhook configuration missing'], 400);
    }

    try {
        //consturctEvent SignatureVerify Krta he
        $event = \Stripe\Webhook::constructEvent(
            $request->getContent(),
            $signature,
            $secret,
        );
    } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $exception) {
        return response()->json(['error' => 'Invalid webhook'], 400);
    }

    Log::info('Verified Stripe webhook: '.$event->type);

    return response()->json(['received' => true]);
});


Route::get('/', function () {
    $movieSlug = config('cinema.featured_movie');
    $movie = config('cinema.movies.'.$movieSlug);

    return view('movies.index', ['movie' => $movie]);
})->name('movies.index');

Route::get('/movies/{movieSlug}/seats', function (string $movieSlug) {
    $movie = config('cinema.movies.'.$movieSlug);

    abort_if(! is_array($movie), 404);

    return view('movies.seats', ['movie' => $movie]);
})->name('movies.seats');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/staff.php';
