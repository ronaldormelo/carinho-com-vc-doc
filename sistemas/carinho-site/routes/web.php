<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\LeadFormController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rotas publicas do site institucional Carinho com Voce.
|
*/

// ==========================================================================
// Paginas Institucionais
// ==========================================================================

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/quem-somos', [PageController::class, 'about'])->name('about');
Route::get('/servicos', [PageController::class, 'services'])->name('services');
Route::get('/como-funciona', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/contato', [PageController::class, 'contact'])->name('contact');
Route::get('/investidores', [PageController::class, 'investors'])->name('investors');

// ==========================================================================
// Paginas por Publico
// ==========================================================================

Route::get('/clientes', [PageController::class, 'forClients'])->name('clients');
Route::get('/cuidadores', [PageController::class, 'forCaregivers'])->name('caregivers');

// ==========================================================================
// Formularios de Lead
// ==========================================================================

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/lead/cliente', [LeadFormController::class, 'submitClientLead'])
        ->name('lead.client.submit');

    Route::post('/lead/cuidador', [LeadFormController::class, 'submitCaregiverLead'])
        ->name('lead.caregiver.submit');

    Route::post('/lead/investidor', [LeadFormController::class, 'submitInvestorLead'])
        ->name('lead.investor.submit');
});

// ==========================================================================
// Paginas Legais
// ==========================================================================

Route::prefix('legal')->group(function () {
    Route::get('/privacidade', [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/termos', [LegalController::class, 'terms'])->name('legal.terms');
    Route::get('/cancelamento', [LegalController::class, 'cancellation'])->name('legal.cancellation');
    Route::get('/pagamento', [LegalController::class, 'payment'])->name('legal.payment');
    Route::get('/emergencias', [LegalController::class, 'emergency'])->name('legal.emergency');
    Route::get('/termos-cuidador', [LegalController::class, 'caregiverTerms'])->name('legal.caregiver-terms');
});

// Aliases para URLs amigaveis
Route::get('/privacidade', [LegalController::class, 'privacy']);
Route::get('/termos', [LegalController::class, 'terms']);

// ==========================================================================
// WhatsApp CTA
// ==========================================================================

Route::get('/whatsapp', function () {
    $phone = config('branding.contact.whatsapp');
    $message = config('branding.whatsapp_messages.default');

    // Captura UTM da sessao
    $utm = [];
    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $param) {
        if (session()->has($param)) {
            $utm[$param] = session($param);
        }
    }

    // Adiciona UTM a mensagem se existir
    if (!empty($utm)) {
        $message .= "\n\n[Origem: {$utm['utm_source']} / {$utm['utm_medium']} / {$utm['utm_campaign']}]";
    }

    return redirect("https://wa.me/{$phone}?text=" . urlencode($message));
})->name('whatsapp.cta');

// ==========================================================================
// Health Checks
// ==========================================================================

Route::get('/health', [HealthController::class, 'check'])->name('health');
Route::get('/health/detailed', [HealthController::class, 'detailed'])->name('health.detailed');
