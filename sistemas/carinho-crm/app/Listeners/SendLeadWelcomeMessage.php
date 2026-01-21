<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Services\Integrations\ZApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeadWelcomeMessage implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-notifications';
    public int $delay = 30; // Aguarda 30 segundos antes de enviar

    public function __construct(
        protected ZApiService $zApiService
    ) {}

    public function handle(LeadCreated $event): void
    {
        if (!$this->zApiService->isEnabled()) {
            return;
        }

        $lead = $event->lead;

        if (!$lead->phone) {
            return;
        }

        $this->zApiService->sendWelcomeMessage($lead->phone, $lead->name);
    }

    public function shouldQueue(LeadCreated $event): bool
    {
        // Só envia mensagem automática se a origem for do site
        return in_array($event->lead->source, ['site', 'whatsapp', 'landing_page']);
    }
}
