<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catch-all — serve the Vue SPA for every web request.
| All data comes from routes/api.php via /api/...
|--------------------------------------------------------------------------
*/

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
