<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'carinho-atendimento',
        'status' => 'ok',
    ]);
});
