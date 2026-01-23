<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema Carinho Marketing
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'service' => 'Carinho Marketing',
        'version' => config('app.version', '1.0.0'),
        'status' => 'ok',
    ]);
});
