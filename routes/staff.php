<?php

use App\Http\Controllers\Staff\ScanController;
use App\Http\Controllers\Staff\TodayController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff')->name('staff.')->group(function () {
    Route::get('/', TodayController::class)->name('today');

    Route::get('/scan', [ScanController::class, 'index'])->name('scan');
    Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');
});
