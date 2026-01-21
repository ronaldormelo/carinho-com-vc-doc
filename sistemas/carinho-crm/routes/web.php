<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Carinho CRM
|--------------------------------------------------------------------------
|
| Rotas web para interface do CRM
|
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'service' => 'carinho-crm',
    ]);
})->name('health');

// Aceite digital de contrato (público)
Route::get('/contract/{token}/sign', function ($token) {
    // Renderiza página de aceite digital
    return view('contracts.sign', ['token' => $token]);
})->name('contract.sign');

Route::post('/contract/{token}/accept', function ($token) {
    // Processa aceite digital
    // Implementação depende do ContractService
})->name('contract.accept');

// Rotas autenticadas
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

    Route::get('/leads', function () {
        return view('leads.index');
    })->name('leads');

    Route::get('/leads/{id}', function ($id) {
        return view('leads.show', ['id' => $id]);
    })->name('leads.detail');

    Route::get('/clients', function () {
        return view('clients.index');
    })->name('clients');

    Route::get('/clients/{id}', function ($id) {
        return view('clients.show', ['id' => $id]);
    })->name('clients.detail');

    Route::get('/pipeline', function () {
        return view('pipeline.index');
    })->name('pipeline');

    Route::get('/contracts', function () {
        return view('contracts.index');
    })->name('contracts');

    Route::get('/tasks', function () {
        return view('tasks.index');
    })->name('tasks');

    Route::get('/reports', function () {
        return view('reports.index');
    })->name('reports');
});
