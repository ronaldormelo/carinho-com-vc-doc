<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookEventJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use App\Integrations\WhatsApp\ZApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private ZApiClient $whatsappClient
    ) {
    }

    public function whatsapp(Request $request): JsonResponse
    {
        $signature = $request->header('X-Zapi-Signature') ?? $request->header('X-Webhook-Signature');

        if (!$this->whatsappClient->isSignatureValid($request->getContent(), $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $eventId = $this->repository->createWebhookEvent([
            'provider' => 'z-api',
            'event_type' => (string) ($payload['event'] ?? 'message'),
            'payload_json' => json_encode($payload),
            'received_at' => now()->toDateTimeString(),
            'processed_at' => null,
            'status_id' => $this->domainLookup->webhookStatusId('pending'),
        ]);

        ProcessWebhookEventJob::dispatch($eventId);

        return response()->json(['status' => 'accepted'], 202);
    }
}
