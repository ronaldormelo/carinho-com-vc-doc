<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Lead::class => \App\Policies\LeadPolicy::class,
        \App\Models\Client::class => \App\Policies\ClientPolicy::class,
        \App\Models\Deal::class => \App\Policies\DealPolicy::class,
        \App\Models\Contract::class => \App\Policies\ContractPolicy::class,
        \App\Models\Task::class => \App\Policies\TaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Super Admin bypass
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // Gates para permissões específicas do CRM
        Gate::define('view-dashboard', function ($user) {
            return $user->hasPermissionTo('view dashboard');
        });

        Gate::define('manage-pipeline', function ($user) {
            return $user->hasPermissionTo('manage pipeline');
        });

        Gate::define('export-data', function ($user) {
            return $user->hasPermissionTo('export data');
        });

        Gate::define('view-reports', function ($user) {
            return $user->hasPermissionTo('view reports');
        });

        Gate::define('manage-integrations', function ($user) {
            return $user->hasRole(['admin', 'super-admin']);
        });
    }
}
