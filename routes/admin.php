<?php

use App\Http\Controllers\Admin\AdminWorkflowController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CinemaController;
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
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
        Route::get('/movies/create', [MovieController::class, 'create'])->name('movies.create');
        Route::post('/movies', [MovieController::class, 'store'])->name('movies.store');
        Route::get('/movies/{movie}', [MovieController::class, 'show'])->name('movies.show');
        Route::get('/movies/{movie}/edit', [MovieController::class, 'edit'])->name('movies.edit');
        Route::put('/movies/{movie}', [MovieController::class, 'update'])->name('movies.update');
        Route::delete('/movies/{movie}', [MovieController::class, 'destroy'])->name('movies.destroy');
        Route::post('/movies/{movie}/archive', [MovieController::class, 'archive'])->name('movies.archive');
        Route::post('/movies/{movie}/restore', [MovieController::class, 'restore'])->name('movies.restore');
        Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
        Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
        Route::put('/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
        Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');
        Route::get('/cinema', [CinemaController::class, 'index'])->name('cinema.index');
        Route::post('/cinema', [CinemaController::class, 'store'])->name('cinema.store');
        Route::patch('/cinema/{cinema}', [CinemaController::class, 'update'])->name('cinema.update');
        Route::delete('/cinema/{cinema}', [CinemaController::class, 'destroy'])->name('cinema.destroy');
        Route::get('/screens/create', [ScreenController::class, 'create'])->name('screens.create');
        Route::post('/screens', [ScreenController::class, 'store'])->name('screens.store');
        Route::get('/screens/{screen}/edit', [ScreenController::class, 'edit'])->name('screens.edit');
        Route::put('/screens/{screen}', [ScreenController::class, 'update'])->name('screens.update');
        Route::delete('/screens/{screen}', [ScreenController::class, 'destroy'])->name('screens.destroy');
        Route::get('/screens/{screen}/seats', [SeatController::class, 'index'])->name('seats.index');
        Route::patch('/seats/{seat}/toggle-disabled', [SeatController::class, 'toggleDisabled'])->name('seats.toggle-disabled');
        Route::get('/showtimes', [ShowtimeController::class, 'index'])->name('showtimes.index');
        Route::get('/showtimes/create', [ShowtimeController::class, 'create'])->name('showtimes.create');
        Route::post('/showtimes', [ShowtimeController::class, 'store'])->name('showtimes.store');
        Route::get('/showtimes/{showtime}', [ShowtimeController::class, 'show'])->name('showtimes.show');
        Route::get('/showtimes/{showtime}/edit', [ShowtimeController::class, 'edit'])->name('showtimes.edit');
        Route::put('/showtimes/{showtime}', [ShowtimeController::class, 'update'])->name('showtimes.update');
        Route::post('/showtimes/{showtime}/cancel', [ShowtimeController::class, 'cancel'])->name('showtimes.cancel');
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::get('/stripe-events', [StripeEventController::class, 'index'])->name('stripe-events.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::post('/bookings/{booking}/cancel', [AdminWorkflowController::class, 'cancelBooking'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/refunds', [AdminWorkflowController::class, 'issueRefund'])->name('bookings.refunds.store');
        Route::post('/bookings/{booking}/resend-email', [AdminWorkflowController::class, 'resendBookingEmail'])->name('bookings.resend-email');
        Route::post('/refunds/{refund}/process', [AdminWorkflowController::class, 'processRefund'])->name('refunds.process');
        Route::post('/refunds/{refund}/retry', [AdminWorkflowController::class, 'retryRefund'])->name('refunds.retry');
        Route::post('/tickets/{ticket}/void', [AdminWorkflowController::class, 'voidTicket'])->name('tickets.void');
        Route::post('/users/staff', [AdminWorkflowController::class, 'createStaff'])->name('users.staff.store');
        Route::patch('/settings', [AdminWorkflowController::class, 'updateSettings'])->name('settings.update');
        Route::post('/stripe-events/{event}/replay', [AdminWorkflowController::class, 'replayStripeEvent'])->name('stripe-events.replay');
    });
