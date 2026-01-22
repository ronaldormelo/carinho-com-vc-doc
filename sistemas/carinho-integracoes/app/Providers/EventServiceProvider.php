<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Lead Events
        \App\Events\LeadCreated::class => [
            \App\Listeners\SendLeadAutoResponse::class,
            \App\Listeners\SyncLeadToCrm::class,
        ],

        // Client Events
        \App\Events\ClientRegistered::class => [
            \App\Listeners\SendWelcomeEmail::class,
            \App\Listeners\SendWelcomeWhatsApp::class,
        ],

        // Service Events
        \App\Events\ServiceCompleted::class => [
            \App\Listeners\RequestFeedback::class,
            \App\Listeners\SyncServiceToFinanceiro::class,
        ],

        // WhatsApp Events
        \App\Events\WhatsAppMessageReceived::class => [
            \App\Listeners\ProcessInboundWhatsApp::class,
            \App\Listeners\RegisterInteractionInCrm::class,
        ],

        // Sync Events
        \App\Events\SyncRequested::class => [
            \App\Listeners\ProcessSyncJob::class,
        ],

        // Integration Events
        \App\Events\IntegrationEventCreated::class => [
            \App\Listeners\DispatchEventProcessing::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
