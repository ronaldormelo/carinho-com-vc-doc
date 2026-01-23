<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Integrations\ZApiService;
use App\Services\Integrations\CarinhoSiteService;
use App\Services\Integrations\CarinhoAtendimentoService;
use App\Services\Integrations\CarinhoOperacaoService;
use App\Services\Integrations\CarinhoFinanceiroService;
use App\Services\Integrations\CarinhoDocumentosService;
use App\Services\Integrations\CarinhoCuidadoresService;
use App\Services\Integrations\CarinhoMarketingService;

class IntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register integration services.
     */
    public function register(): void
    {
        // Z-API WhatsApp Integration
        $this->app->singleton(ZApiService::class, function ($app) {
            return new ZApiService(
                config('integrations.zapi.base_url'),
                config('integrations.zapi.instance_id'),
                config('integrations.zapi.token'),
                config('integrations.zapi.client_token')
            );
        });

        // Carinho Site Integration
        $this->app->singleton(CarinhoSiteService::class, function ($app) {
            return new CarinhoSiteService(
                config('integrations.internal.site.base_url'),
                config('integrations.internal.site.api_key')
            );
        });

        // Carinho Marketing Integration
        $this->app->singleton(CarinhoMarketingService::class, function ($app) {
            return new CarinhoMarketingService(
                config('integrations.internal.marketing.base_url'),
                config('integrations.internal.marketing.api_key')
            );
        });

        // Carinho Atendimento Integration
        $this->app->singleton(CarinhoAtendimentoService::class, function ($app) {
            return new CarinhoAtendimentoService(
                config('integrations.internal.atendimento.base_url'),
                config('integrations.internal.atendimento.api_key')
            );
        });

        // Carinho Operação Integration
        $this->app->singleton(CarinhoOperacaoService::class, function ($app) {
            return new CarinhoOperacaoService(
                config('integrations.internal.operacao.base_url'),
                config('integrations.internal.operacao.api_key')
            );
        });

        // Carinho Financeiro Integration
        $this->app->singleton(CarinhoFinanceiroService::class, function ($app) {
            return new CarinhoFinanceiroService(
                config('integrations.internal.financeiro.base_url'),
                config('integrations.internal.financeiro.api_key')
            );
        });

        // Carinho Documentos Integration
        $this->app->singleton(CarinhoDocumentosService::class, function ($app) {
            return new CarinhoDocumentosService(
                config('integrations.internal.documentos.base_url'),
                config('integrations.internal.documentos.api_key')
            );
        });

        // Carinho Cuidadores Integration
        $this->app->singleton(CarinhoCuidadoresService::class, function ($app) {
            return new CarinhoCuidadoresService(
                config('integrations.internal.cuidadores.base_url'),
                config('integrations.internal.cuidadores.api_key')
            );
        });
    }

    /**
     * Bootstrap integration services.
     */
    public function boot(): void
    {
        //
    }
}
