<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema Carinho Cuidadores
|--------------------------------------------------------------------------
|
| Rotas web para formularios publicos e paginas de assinatura.
|
*/

// Pagina inicial - Formulario de cadastro
Route::get('/', function () {
    return view('cadastro');
})->name('home');

// Formulario de cadastro de cuidador (publico)
Route::get('/cadastro', function () {
    return view('cadastro');
})->name('cadastro');

// Pagina de assinatura de contrato
Route::get('/contratos/{id}/assinar', function ($id) {
    return view('contrato-assinar', ['contractId' => $id]);
})->name('contrato.assinar');

// Pagina de confirmacao
Route::get('/confirmacao', function () {
    return view('confirmacao');
})->name('confirmacao');
