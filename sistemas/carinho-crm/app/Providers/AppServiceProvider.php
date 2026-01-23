<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar Repositories
        $this->app->bind(
            \App\Repositories\Contracts\LeadRepositoryInterface::class,
            \App\Repositories\LeadRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\ClientRepositoryInterface::class,
            \App\Repositories\ClientRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\DealRepositoryInterface::class,
            \App\Repositories\DealRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\ContractRepositoryInterface::class,
            \App\Repositories\ContractRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\TaskRepositoryInterface::class,
            \App\Repositories\TaskRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\InteractionRepositoryInterface::class,
            \App\Repositories\InteractionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Definir comprimento padrão de string para MySQL < 5.7.7
        Schema::defaultStringLength(191);

        // Habilitar modo strict do Eloquent em desenvolvimento
        Model::shouldBeStrict(! $this->app->isProduction());

        // Prevenir lazy loading em desenvolvimento
        Model::preventLazyLoading(! $this->app->isProduction());

        // Prevenir silently discarding attributes
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
