<?php

use App\Enums\Role;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShowtimeController;
use App\Models\Movie;
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

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
Route::get('/movies/{movie}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/showtimes/{showtime}', [ShowtimeController::class, 'show'])->name('showtimes.show');
Route::get('/movies/{movie}/seats', function (Movie $movie, Request $request) {
    $time = $request->query('time');

    if (filled($time)) {
        abort_unless(is_string($time) && preg_match('/^\d{2}:\d{2}(?::\d{2})?$/D', $time), 404);
        $time = strlen($time) === 5 ? $time.':00' : $time;
    }

    $showtime = $movie->showtimes()
        ->scheduled()
        ->where('starts_at', '>', now())
        ->when($request->filled('date'), fn ($query) => $query->whereDate('starts_at', $request->query('date')))
        ->when(filled($time), fn ($query) => $query->whereTime('starts_at', $time))
        ->orderBy('starts_at')
        ->firstOrFail();

    return redirect()->route('showtimes.show', $showtime);
})->name('movies.seats');

Route::get('/dashboard', function () {
    if (auth()->user()->role === Role::Admin) {
        return redirect()->route('admin.dashboard');
    }

    return match (auth()->user()->role) {
        Role::Staff => view('dashboard.staff'),
        Role::Customer => view('dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
