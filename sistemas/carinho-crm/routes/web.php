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

    // =====================================================================
    // Gestão de Conteúdo do Site
    // =====================================================================
    Route::prefix('content')->name('content.')->group(function () {
        // Testimonials
        Route::get('/testimonials', [\App\Http\Controllers\ContentController::class, 'testimonials'])->name('testimonials');
        Route::get('/testimonials/create', [\App\Http\Controllers\ContentController::class, 'testimonialForm'])->name('testimonials.create');
        Route::get('/testimonials/{id}/edit', [\App\Http\Controllers\ContentController::class, 'testimonialForm'])->name('testimonials.edit');
        Route::post('/testimonials', [\App\Http\Controllers\ContentController::class, 'saveTestimonial'])->name('testimonials.store');
        Route::put('/testimonials/{id}', [\App\Http\Controllers\ContentController::class, 'saveTestimonial'])->name('testimonials.update');
        Route::delete('/testimonials/{id}', [\App\Http\Controllers\ContentController::class, 'deleteTestimonial'])->name('testimonials.destroy');

        // FAQ Categories
        Route::get('/faq/categories', [\App\Http\Controllers\ContentController::class, 'faqCategories'])->name('faq.categories');
        Route::get('/faq/categories/create', [\App\Http\Controllers\ContentController::class, 'faqCategoryForm'])->name('faq.categories.create');
        Route::get('/faq/categories/{id}/edit', [\App\Http\Controllers\ContentController::class, 'faqCategoryForm'])->name('faq.categories.edit');
        Route::post('/faq/categories', [\App\Http\Controllers\ContentController::class, 'saveFaqCategory'])->name('faq.categories.store');
        Route::put('/faq/categories/{id}', [\App\Http\Controllers\ContentController::class, 'saveFaqCategory'])->name('faq.categories.update');
        Route::delete('/faq/categories/{id}', [\App\Http\Controllers\ContentController::class, 'deleteFaqCategory'])->name('faq.categories.destroy');

        // FAQ Items
        Route::get('/faq/categories/{categoryId}/items', [\App\Http\Controllers\ContentController::class, 'faqItems'])->name('faq.items');
        Route::get('/faq/categories/{categoryId}/items/create', [\App\Http\Controllers\ContentController::class, 'faqItemForm'])->name('faq.items.create');
        Route::get('/faq/categories/{categoryId}/items/{itemId}/edit', [\App\Http\Controllers\ContentController::class, 'faqItemForm'])->name('faq.items.edit');
        Route::post('/faq/categories/{categoryId}/items', [\App\Http\Controllers\ContentController::class, 'saveFaqItem'])->name('faq.items.store');
        Route::put('/faq/categories/{categoryId}/items/{itemId}', [\App\Http\Controllers\ContentController::class, 'saveFaqItem'])->name('faq.items.update');
        Route::delete('/faq/categories/{categoryId}/items/{itemId}', [\App\Http\Controllers\ContentController::class, 'deleteFaqItem'])->name('faq.items.destroy');

        // Pages
        Route::get('/pages', [\App\Http\Controllers\ContentController::class, 'pages'])->name('pages');
        Route::get('/pages/create', [\App\Http\Controllers\ContentController::class, 'pageForm'])->name('pages.create');
        Route::get('/pages/{id}/edit', [\App\Http\Controllers\ContentController::class, 'pageForm'])->name('pages.edit');
        Route::post('/pages', [\App\Http\Controllers\ContentController::class, 'savePage'])->name('pages.store');
        Route::put('/pages/{id}', [\App\Http\Controllers\ContentController::class, 'savePage'])->name('pages.update');
        Route::delete('/pages/{id}', [\App\Http\Controllers\ContentController::class, 'deletePage'])->name('pages.destroy');
    });
});
