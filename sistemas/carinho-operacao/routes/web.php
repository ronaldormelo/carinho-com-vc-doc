<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Carinho Operacao
|--------------------------------------------------------------------------
|
| Rotas web para o sistema de Operacao.
| Subdominio: operacao.carinho.com.vc
|
*/

Route::get('/', function () {
    return response()->json([
        'service' => 'carinho-operacao',
        'status' => 'running',
        'documentation' => '/api/docs',
    ]);
});
