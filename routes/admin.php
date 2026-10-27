<?php

use App\Http\Controllers\Admin\AdminPlaceholderController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CinemaController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\MovieController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ScreenController;
use App\Http\Controllers\Admin\SeatController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShowtimeController;
use App\Http\Controllers\Admin\StripeEventController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/movies/create', [MovieController::class, 'create'])->name('movies.create');
        Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
        Route::post('/movies', [MovieController::class, 'store'])->name('movies.store');
        Route::get('/movies/{movie}/edit', [MovieController::class, 'edit'])->name('movies.edit');
        Route::put('/movies/{movie}', [MovieController::class, 'update'])->name('movies.update');
        Route::delete('/movies/{movie}', [MovieController::class, 'destroy'])->name('movies.destroy');
        Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
        Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])->name('genres.edit');
        Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
        Route::put('/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
        Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');
        Route::get('/cinema', [CinemaController::class, 'index'])->name('cinema.index');
        Route::post('/cinema', [CinemaController::class, 'store'])->name('cinema.store');
        Route::put('/cinema/{cinema}', [CinemaController::class, 'update'])->name('cinema.update');
        Route::delete('/cinema/{cinema}', [CinemaController::class, 'destroy'])->name('cinema.destroy');
        Route::get('/screens', [ScreenController::class, 'index'])->name('screens.index');
        Route::get('/screens/create', [ScreenController::class, 'create'])->name('screens.create');
        Route::post('/screens', [ScreenController::class, 'store'])->name('screens.store');
        Route::get('/screens/{screen}', [ScreenController::class, 'show'])->name('screens.show');
        Route::get('/screens/{screen}/edit', [ScreenController::class, 'edit'])->name('screens.edit');
        Route::put('/screens/{screen}', [ScreenController::class, 'update'])->name('screens.update');
        Route::delete('/screens/{screen}', [ScreenController::class, 'destroy'])->name('screens.destroy');
        Route::post('/screens/{screen}/seats', [SeatController::class, 'generate'])->name('screens.seats.generate');
        Route::put('/screens/{screen}/seats/{seat}', [SeatController::class, 'update'])->name('screens.seats.update');
        Route::get('/showtimes', [ShowtimeController::class, 'index'])->name('showtimes.index');
        Route::get('/showtimes/create', [ShowtimeController::class, 'create'])->name('showtimes.create');
        Route::post('/showtimes', [ShowtimeController::class, 'store'])->name('showtimes.store');
        Route::get('/showtimes/{showtime}/edit', [ShowtimeController::class, 'edit'])->name('showtimes.edit');
        Route::put('/showtimes/{showtime}', [ShowtimeController::class, 'update'])->name('showtimes.update');
        Route::get('/showtimes/{showtime}/cancel', [ShowtimeController::class, 'confirmCancel'])->name('showtimes.cancel.confirm');
        Route::post('/showtimes/{showtime}/cancel', [ShowtimeController::class, 'cancel'])->name('showtimes.cancel');
        Route::get('/bookings/export', [BookingController::class, 'export'])->name('bookings.export');
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/refund', [BookingController::class, 'refund'])->name('bookings.refund');
        Route::post('/bookings/{booking}/tickets/{ticket}/void', [BookingController::class, 'voidTicket'])->name('bookings.tickets.void');
        Route::post('/bookings/{booking}/resend-email', [BookingController::class, 'resendEmail'])->name('bookings.resend-email');
        Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/refunds/export', [RefundController::class, 'export'])->name('refunds.export');
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/staff-admins', [AdminPlaceholderController::class, 'staffAdmins'])->name('staff-admins.index');
        Route::get('/reports', [AdminPlaceholderController::class, 'reports'])->name('reports.index');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::get('/failed-jobs', [AdminPlaceholderController::class, 'failedJobs'])->name('failed-jobs.index');
        Route::get('/stripe-events', [StripeEventController::class, 'index'])->name('stripe-events.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
