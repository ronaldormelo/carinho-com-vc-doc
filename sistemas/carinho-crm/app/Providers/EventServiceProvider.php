<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
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
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Eventos de Lead
        \App\Events\LeadCreated::class => [
            \App\Listeners\NotifyAtendimentoNewLead::class,
            \App\Listeners\SendLeadWelcomeMessage::class,
            \App\Listeners\LogLeadActivity::class,
        ],

        \App\Events\LeadStatusChanged::class => [
            \App\Listeners\UpdatePipelineMetrics::class,
            \App\Listeners\SyncLeadStatusWithAtendimento::class,
            \App\Listeners\LogLeadActivity::class,
        ],

        \App\Events\LeadConverted::class => [
            \App\Listeners\CreateClientFromLead::class,
            \App\Listeners\NotifyOperacaoNewClient::class,
            \App\Listeners\LogLeadActivity::class,
        ],

        \App\Events\LeadLost::class => [
            \App\Listeners\RecordLossReason::class,
            \App\Listeners\UpdateConversionMetrics::class,
            \App\Listeners\LogLeadActivity::class,
        ],

        // Eventos de Deal
        \App\Events\DealCreated::class => [
            \App\Listeners\LogDealActivity::class,
        ],

        \App\Events\DealStageChanged::class => [
            \App\Listeners\NotifyDealStageChange::class,
            \App\Listeners\LogDealActivity::class,
        ],

        \App\Events\DealWon::class => [
            \App\Listeners\CreateContractFromDeal::class,
            \App\Listeners\NotifyFinanceiroNewContract::class,
            \App\Listeners\LogDealActivity::class,
        ],

        // Eventos de Contrato
        \App\Events\ContractSigned::class => [
            \App\Listeners\ActivateClient::class,
            \App\Listeners\NotifyDocumentosNewContract::class,
            \App\Listeners\SyncContractWithFinanceiro::class,
            \App\Listeners\LogContractActivity::class,
        ],

        \App\Events\ContractExpiring::class => [
            \App\Listeners\CreateRenewalTask::class,
            \App\Listeners\NotifyClientContractExpiring::class,
        ],

        // Eventos de Interação
        \App\Events\InteractionRecorded::class => [
            \App\Listeners\UpdateLastContactDate::class,
            \App\Listeners\LogInteractionActivity::class,
        ],

        // Eventos de Task
        \App\Events\TaskCreated::class => [
            \App\Listeners\NotifyTaskAssignee::class,
        ],

        \App\Events\TaskOverdue::class => [
            \App\Listeners\EscalateOverdueTask::class,
        ],
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
