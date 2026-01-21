<?php

namespace App\Listeners;

use App\Events\ContractSigned;
use App\Services\Integrations\CarinhoDocumentosService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyDocumentosNewContract implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'crm-integrations';

    public function __construct(
        protected CarinhoDocumentosService $documentosService
    ) {}

    public function handle(ContractSigned $event): void
    {
        $contract = $event->contract;
        $contract->load('client.lead');

        $this->documentosService->storeDocument(
            $contract->client_id,
            'contract_signed',
            "contracts/{$contract->id}/document.pdf",
            [
                'contract_id' => $contract->id,
                'signed_at' => $contract->signed_at?->toIso8601String(),
            ]
        );
    }
}
