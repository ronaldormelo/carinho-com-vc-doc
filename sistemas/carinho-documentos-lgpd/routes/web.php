<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Sistema: Carinho Documentos e LGPD
| Subdominio: documentos.carinho.com.vc
|
| As rotas web sao usadas principalmente para:
| - Paginas publicas de visualizacao de termos e politicas
| - Interface de assinatura de contratos
| - Downloads de documentos com URLs assinadas
|
*/

Route::get('/', function () {
    return response()->json([
        'sistema' => 'Carinho Documentos e LGPD',
        'versao' => '1.0.0',
        'status' => 'online',
        'subdominio' => 'documentos.carinho.com.vc',
    ]);
});

// Pagina de assinatura de contrato (interface web)
Route::get('/assinar/{token}', function (string $token) {
    return view('contracts.sign', ['token' => $token]);
})->name('contract.sign');

// Paginas publicas de termos e politicas
Route::get('/termos', function () {
    return view('public.terms');
})->name('terms');

Route::get('/privacidade', function () {
    return view('public.privacy');
})->name('privacy');

// Download de documento com URL assinada
Route::get('/download/{token}', function (string $token) {
    // Validacao e download processados pelo controller
    return app(\App\Http\Controllers\DocumentController::class)->downloadByToken($token);
})->name('document.download');
