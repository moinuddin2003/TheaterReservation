<?php

use App\Http\Controllers\Staff\LookupController;
use App\Http\Controllers\Staff\RosterController;
use App\Http\Controllers\Staff\ScanController;
use App\Http\Controllers\Staff\TodayController;
use Illuminate\Support\Facades\Route;

// Staff area: logged-in staff and admin only (guests are sent to login, customers get a 403).
Route::middleware(['auth', 'role:staff,admin'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', TodayController::class)->name('today');

    Route::get('/scan', [ScanController::class, 'index'])->name('scan');
    Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');

    Route::get('/roster', RosterController::class)->name('roster');

    Route::get('/lookup', LookupController::class)->name('lookup');
});
