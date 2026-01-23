<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Integrations\WhatsApp\ZApiClient;
use App\Services\Integrations\Crm\CrmClient;
use App\Services\Integrations\Operacao\OperacaoClient;
use App\Services\Integrations\Financeiro\FinanceiroClient;
use App\Services\Integrations\Cuidadores\CuidadoresClient;
use App\Services\Integrations\Atendimento\AtendimentoClient;
use App\Services\Integrations\Site\SiteClient;
use App\Services\Integrations\Marketing\MarketingClient;
use App\Services\Integrations\Documentos\DocumentosClient;
use App\Services\Email\EmailService;

class IntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // WhatsApp (Z-API) - Singleton
        $this->app->singleton(ZApiClient::class, function ($app) {
            return new ZApiClient();
        });

        // Email Service - Singleton
        $this->app->singleton(EmailService::class, function ($app) {
            return new EmailService();
        });

        // Sistemas Internos - Singleton para reuso de conexoes
        $this->app->singleton(CrmClient::class, function ($app) {
            return new CrmClient();
        });

        $this->app->singleton(OperacaoClient::class, function ($app) {
            return new OperacaoClient();
        });

        $this->app->singleton(FinanceiroClient::class, function ($app) {
            return new FinanceiroClient();
        });

        $this->app->singleton(CuidadoresClient::class, function ($app) {
            return new CuidadoresClient();
        });

        $this->app->singleton(AtendimentoClient::class, function ($app) {
            return new AtendimentoClient();
        });

        $this->app->singleton(SiteClient::class, function ($app) {
            return new SiteClient();
        });

        $this->app->singleton(MarketingClient::class, function ($app) {
            return new MarketingClient();
        });

        $this->app->singleton(DocumentosClient::class, function ($app) {
            return new DocumentosClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
