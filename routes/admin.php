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

        Route::post('/bookings/{booking}/cancel', [AdminWorkflowController::class, 'cancelBooking'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/refunds', [AdminWorkflowController::class, 'issueRefund'])->name('bookings.refunds.store');
        Route::post('/bookings/{booking}/resend-email', [AdminWorkflowController::class, 'resendBookingEmail'])->name('bookings.resend-email');
        Route::post('/refunds/{refund}/process', [AdminWorkflowController::class, 'processRefund'])->name('refunds.process');
        Route::post('/refunds/{refund}/retry', [AdminWorkflowController::class, 'retryRefund'])->name('refunds.retry');
        Route::post('/showtimes/{showtime}/cancel', [AdminWorkflowController::class, 'cancelShowtime'])->name('showtimes.cancel');
        Route::post('/tickets/{ticket}/void', [AdminWorkflowController::class, 'voidTicket'])->name('tickets.void');
        Route::post('/users/staff', [AdminWorkflowController::class, 'createStaff'])->name('users.staff.store');
        Route::patch('/settings', [AdminWorkflowController::class, 'updateSettings'])->name('settings.update');
        Route::post('/stripe-events/{event}/replay', [AdminWorkflowController::class, 'replayStripeEvent'])->name('stripe-events.replay');
    });
