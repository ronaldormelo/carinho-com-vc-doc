<?php

namespace App\Jobs;

use App\Integrations\WhatsApp\ZApiClient;
use App\Repositories\AtendimentoRepository;
use App\Services\SlaService;
use App\Support\DomainLookup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private int $conversationId,
        private int $messageId,
        private string $phone,
        private string $body,
        private ?string $mediaUrl = null
    ) {
    }

    public function handle(
        ZApiClient $client,
        AtendimentoRepository $repository,
        DomainLookup $domainLookup,
        SlaService $slaService
    ): void {
        $response = $this->mediaUrl
            ? $client->sendMediaMessage($this->phone, $this->mediaUrl, $this->body)
            : $client->sendTextMessage($this->phone, $this->body);

        if ($response['ok']) {
            $sentAt = now()->toDateTimeString();
            $repository->updateMessageStatus(
                $this->messageId,
                $domainLookup->messageStatusId('sent'),
                $sentAt
            );
            $slaService->recordFirstResponse($this->conversationId, $sentAt);
            return;
        }

        $repository->updateMessageStatus(
            $this->messageId,
            $domainLookup->messageStatusId('failed')
        );
    }
}
