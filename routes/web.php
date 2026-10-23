<?php

use App\Enums\Role;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\StripeWebhookController;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
Route::get('/movies/{movie}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/showtimes/{showtime}', [ShowtimeController::class, 'show'])->name('showtimes.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/showtimes/{showtime}/holds', [BookingController::class, 'holdSeats'])
        ->name('showtimes.holds.store');
    Route::post('/bookings/{reference}/checkout', [BookingController::class, 'startCheckout'])
        ->name('bookings.checkout.start');
});

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
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{reference}/checkout', [BookingController::class, 'checkout'])->name('bookings.checkout');
    Route::get('/bookings/{reference}/processing', [BookingController::class, 'processing'])->name('bookings.processing');
    Route::get('/bookings/{reference}/confirmed', [BookingController::class, 'confirmed'])->name('bookings.confirmed');
    Route::get('/bookings/{reference}/cancel', [BookingController::class, 'cancelConfirmation'])
        ->name('bookings.cancel');
    Route::post('/bookings/{reference}/cancel', [BookingController::class, 'cancel'])
        ->name('bookings.cancel.request');
    Route::get('/bookings/{reference}/tickets/{ticketId}', [BookingController::class, 'ticket'])
        ->whereNumber('ticketId')
        ->name('bookings.tickets.show');
    Route::get('/bookings/{reference}', [BookingController::class, 'show'])->name('bookings.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/staff.php';
