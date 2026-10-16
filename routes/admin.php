<?php

use App\Http\Controllers\Admin\GenreController;
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
