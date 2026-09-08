<?php

use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegistrationController::class, 'form'])->name('home');
Route::get('/cadastro', [RegistrationController::class, 'form'])->name('cadastro');
Route::post('/cadastro', [RegistrationController::class, 'store'])->name('cadastro.store');
Route::get('/confirmacao', [RegistrationController::class, 'confirmation'])->name('confirmacao');

Route::get('/contratos/{id}/assinar', function ($id) {
    abort(404, 'Pagina de assinatura ainda nao disponivel neste modulo.');
})->name('contrato.assinar');
