<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Email verification link
|--------------------------------------------------------------------------
| Opened directly from the verification email, outside the SPA/API token
| flow — the signature + hash-of-email prove ownership, not a bearer token.
| Must be registered before the catch-all below.
*/
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Catch-all — serve the Vue SPA for every web request.
| All data comes from routes/api.php via /api/...
|--------------------------------------------------------------------------
*/

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
