<?php

use App\Http\Controllers\Staff\TodayController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff')->name('staff.')->group(function () {
    Route::get('/', TodayController::class)->name('today');
});
