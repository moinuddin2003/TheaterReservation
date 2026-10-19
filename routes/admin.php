<?php

use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\MovieController;
use App\Http\Controllers\Admin\ScreenController;
use App\Http\Controllers\Admin\SeatController;
use App\Http\Controllers\Admin\ShowtimeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
|
| Loaded under the /admin prefix with the admin. route name prefix and the
| auth + role:admin middleware (see bootstrap/app.php).
|
*/

Route::resource('genres', GenreController::class)->except('show');
Route::resource('movies', MovieController::class)->except('show');
Route::resource('screens', ScreenController::class);

Route::post('screens/{screen}/seats/generate', [SeatController::class, 'generate'])->name('screens.seats.generate');
Route::patch('screens/{screen}/seats/{seat}', [SeatController::class, 'update'])->name('screens.seats.update');

Route::resource('showtimes', ShowtimeController::class)->except(['show', 'destroy']);
Route::get('showtimes/{showtime}/cancel', [ShowtimeController::class, 'confirmCancel'])->name('showtimes.cancel');
Route::post('showtimes/{showtime}/cancel', [ShowtimeController::class, 'cancel'])->name('showtimes.cancel.store');
