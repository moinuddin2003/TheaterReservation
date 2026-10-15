<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::post('/stripe/webhook', function (Request $request) {
    $signature = $request->header('Stripe-Signature');
    $secret = config('services.stripe.webhook_secret');

    if (! $signature || ! $secret) {
        return response()->json(['error' => 'Webhook configuration missing'], 400);
    }

    try {
        //consturctEvent SignatureVerify Krta he
        $event = \Stripe\Webhook::constructEvent(
            $request->getContent(),
            $signature,
            $secret,
        );
    } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $exception) {
        return response()->json(['error' => 'Invalid webhook'], 400);
    }

    Log::info('Verified Stripe webhook: '.$event->type);

    return response()->json(['received' => true]);
});


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';



