<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CinemaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\MovieController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\RefundController;
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
        Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
        Route::get('/cinema', [CinemaController::class, 'index'])->name('cinema.index');
        Route::get('/showtimes', [ShowtimeController::class, 'index'])->name('showtimes.index');
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::get('/stripe-events', [StripeEventController::class, 'index'])->name('stripe-events.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
