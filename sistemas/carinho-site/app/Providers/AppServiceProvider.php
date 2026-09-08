<?php

namespace App\Providers;

use App\Services\CrmClient;
use App\Services\RecaptchaService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registra servicos como singletons
        $this->app->singleton(CrmClient::class);
        $this->app->singleton(WhatsAppService::class);
        $this->app->singleton(RecaptchaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['partials.header', 'partials.whatsapp-float'], function ($view) {
            $key = app(WhatsAppService::class)->messageKeyForRoute(request()->route()?->getName());
            $view->with('whatsappCtaUrl', route('whatsapp.cta', ['msg' => $key]));
        });
    }
}
