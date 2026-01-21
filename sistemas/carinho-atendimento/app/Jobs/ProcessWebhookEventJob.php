<?php

namespace App\Jobs;

use App\Repositories\AtendimentoRepository;
use App\Services\InboxService;
use App\Support\DomainLookup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessWebhookEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private int $webhookEventId)
    {
    }

    public function handle(
        AtendimentoRepository $repository,
        InboxService $inboxService,
        DomainLookup $domainLookup
    ): void {
        $event = DB::table('webhook_events')->where('id', $this->webhookEventId)->first();

        if (!$event) {
            return;
        }

        $payload = json_decode($event->payload_json, true) ?? [];

        try {
            if ($event->provider === 'z-api') {
                $inboxService->handleInboundMessage($payload);
            }

            $repository->updateWebhookEvent($event->id, [
                'status_id' => $domainLookup->webhookStatusId('processed'),
                'processed_at' => now()->toDateTimeString(),
            ]);
        } catch (\Throwable $exception) {
            $repository->updateWebhookEvent($event->id, [
                'status_id' => $domainLookup->webhookStatusId('failed'),
                'processed_at' => now()->toDateTimeString(),
            ]);

            throw $exception;
        }
    }
}
