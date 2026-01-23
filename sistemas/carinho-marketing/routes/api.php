<?php

use App\Http\Controllers\BrandLibraryController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContentCalendarController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Controllers\UtmController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema Carinho Marketing
|--------------------------------------------------------------------------
|
| Rotas da API RESTful para gestao de marketing.
| Subdominio: marketing.carinho.com.vc
|
*/

// Health check (publico)
Route::get('/health', [HealthController::class, 'show']);

// Webhooks (com validacao propria)
Route::prefix('webhooks')->group(function () {
    Route::post('/whatsapp/z-api', [WebhookController::class, 'whatsapp']);
    Route::match(['get', 'post'], '/meta', [WebhookController::class, 'meta']);
    Route::post('/google-ads', [WebhookController::class, 'googleAds']);
    Route::post('/conversion', [WebhookController::class, 'conversion']);
});

// Rotas protegidas por token interno
Route::middleware(['internal.token'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Calendario Editorial
    |--------------------------------------------------------------------------
    */
    Route::prefix('calendar')->group(function () {
        Route::get('/', [ContentCalendarController::class, 'index']);
        Route::get('/this-week', [ContentCalendarController::class, 'thisWeek']);
        Route::get('/stats', [ContentCalendarController::class, 'stats']);
        Route::post('/', [ContentCalendarController::class, 'store']);
        Route::get('/{id}', [ContentCalendarController::class, 'show']);
        Route::put('/{id}', [ContentCalendarController::class, 'update']);
        Route::post('/{id}/schedule', [ContentCalendarController::class, 'schedule']);
        Route::post('/{id}/cancel-schedule', [ContentCalendarController::class, 'cancelSchedule']);
        Route::post('/{id}/publish', [ContentCalendarController::class, 'publish']);
        Route::post('/{id}/assets', [ContentCalendarController::class, 'addAsset']);
        Route::delete('/{id}/assets/{assetId}', [ContentCalendarController::class, 'removeAsset']);
        Route::post('/assets/{assetId}/approve', [ContentCalendarController::class, 'approveAsset']);
    });

    /*
    |--------------------------------------------------------------------------
    | Campanhas
    |--------------------------------------------------------------------------
    */
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::get('/dashboard', [CampaignController::class, 'dashboard']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/{id}', [CampaignController::class, 'show']);
        Route::put('/{id}', [CampaignController::class, 'update']);
        Route::post('/{id}/activate', [CampaignController::class, 'activate']);
        Route::post('/{id}/pause', [CampaignController::class, 'pause']);
        Route::post('/{id}/finish', [CampaignController::class, 'finish']);
        Route::get('/{id}/metrics', [CampaignController::class, 'metrics']);
        Route::get('/{id}/metrics/daily', [CampaignController::class, 'dailyMetrics']);
        Route::post('/{id}/sync-metrics', [CampaignController::class, 'syncMetrics']);
        Route::post('/{campaignId}/ad-groups', [CampaignController::class, 'addAdGroup']);
        Route::put('/ad-groups/{adGroupId}', [CampaignController::class, 'updateAdGroup']);
        Route::post('/ad-groups/{adGroupId}/creatives', [CampaignController::class, 'addCreative']);
    });

    /*
    |--------------------------------------------------------------------------
    | Landing Pages
    |--------------------------------------------------------------------------
    */
    Route::prefix('landing-pages')->group(function () {
        Route::get('/', [LandingPageController::class, 'index']);
        Route::get('/published', [LandingPageController::class, 'published']);
        Route::post('/', [LandingPageController::class, 'store']);
        Route::get('/{id}', [LandingPageController::class, 'show']);
        Route::put('/{id}', [LandingPageController::class, 'update']);
        Route::post('/{id}/publish', [LandingPageController::class, 'publish']);
        Route::post('/{id}/archive', [LandingPageController::class, 'archive']);
        Route::post('/{id}/utm', [LandingPageController::class, 'setUtm']);
        Route::get('/{id}/stats', [LandingPageController::class, 'stats']);
        Route::get('/{id}/url', [LandingPageController::class, 'generateUrl']);
    });

    /*
    |--------------------------------------------------------------------------
    | UTM Builder
    |--------------------------------------------------------------------------
    */
    Route::prefix('utm')->group(function () {
        Route::get('/', [UtmController::class, 'index']);
        Route::post('/', [UtmController::class, 'store']);
        Route::get('/sources', [UtmController::class, 'sources']);
        Route::get('/mediums', [UtmController::class, 'mediums']);
        Route::get('/{id}', [UtmController::class, 'show']);
        Route::post('/build', [UtmController::class, 'buildUrl']);
        Route::post('/build-whatsapp', [UtmController::class, 'buildWhatsAppUrl']);
        Route::post('/build-bio', [UtmController::class, 'buildBioUrl']);
        Route::post('/build-campaign', [UtmController::class, 'buildCampaignUrl']);
        Route::post('/parse', [UtmController::class, 'parseUrl']);
    });

    /*
    |--------------------------------------------------------------------------
    | Conversoes
    |--------------------------------------------------------------------------
    */
    Route::prefix('conversions')->group(function () {
        Route::post('/lead', [ConversionController::class, 'registerLead']);
        Route::post('/contact', [ConversionController::class, 'registerContact']);
        Route::post('/registration', [ConversionController::class, 'registerRegistration']);
        Route::get('/events', [ConversionController::class, 'listEvents']);
        Route::post('/events', [ConversionController::class, 'createEvent']);
        Route::get('/stats', [ConversionController::class, 'stats']);
    });

    /*
    |--------------------------------------------------------------------------
    | Contas Sociais
    |--------------------------------------------------------------------------
    */
    Route::prefix('social-accounts')->group(function () {
        Route::get('/', [SocialAccountController::class, 'index']);
        Route::get('/channels', [SocialAccountController::class, 'channels']);
        Route::post('/channels', [SocialAccountController::class, 'createChannel']);
        Route::get('/stats', [SocialAccountController::class, 'stats']);
        Route::post('/', [SocialAccountController::class, 'store']);
        Route::get('/{id}', [SocialAccountController::class, 'show']);
        Route::put('/{id}', [SocialAccountController::class, 'update']);
        Route::post('/{id}/activate', [SocialAccountController::class, 'activate']);
        Route::post('/{id}/deactivate', [SocialAccountController::class, 'deactivate']);
        Route::post('/{id}/sync-instagram', [SocialAccountController::class, 'syncInstagram']);
        Route::post('/{id}/sync-facebook', [SocialAccountController::class, 'syncFacebook']);
        Route::get('/{id}/bio', [SocialAccountController::class, 'bio']);
    });

    /*
    |--------------------------------------------------------------------------
    | Biblioteca de Marca
    |--------------------------------------------------------------------------
    */
    Route::prefix('brand')->group(function () {
        Route::get('/config', [BrandLibraryController::class, 'config']);
        Route::get('/colors', [BrandLibraryController::class, 'colors']);
        Route::get('/typography', [BrandLibraryController::class, 'typography']);
        Route::get('/voice', [BrandLibraryController::class, 'voice']);
        Route::get('/messages', [BrandLibraryController::class, 'messages']);
        Route::get('/hashtags', [BrandLibraryController::class, 'hashtags']);
        Route::get('/social-bio', [BrandLibraryController::class, 'socialBio']);
        Route::get('/content-themes', [BrandLibraryController::class, 'contentThemes']);
        Route::get('/css', [BrandLibraryController::class, 'css']);

        // Assets
        Route::get('/assets', [BrandLibraryController::class, 'index']);
        Route::get('/assets/logos', [BrandLibraryController::class, 'logos']);
        Route::get('/assets/logos/primary', [BrandLibraryController::class, 'primaryLogo']);
        Route::get('/assets/templates', [BrandLibraryController::class, 'templates']);
        Route::post('/assets', [BrandLibraryController::class, 'store']);
        Route::get('/assets/{id}', [BrandLibraryController::class, 'show']);
        Route::put('/assets/{id}', [BrandLibraryController::class, 'update']);
        Route::post('/assets/{id}/activate', [BrandLibraryController::class, 'activate']);
        Route::post('/assets/{id}/deactivate', [BrandLibraryController::class, 'deactivate']);
    });
});
