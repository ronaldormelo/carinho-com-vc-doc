<?php

namespace App\Jobs;

use App\Services\ContractService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar notificacao de contrato.
 */
class SendContractNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private int $documentId,
        private string $phone,
        private string $email,
        private string $recipientName,
        private string $signatureUrl
    ) {}

    public function handle(NotificationService $notification): void
    {
        try {
            $notification->notifyContractReady(
                $this->phone,
                $this->email,
                $this->recipientName,
                $this->signatureUrl
            );

            Log::info('Contract notification sent', [
                'document_id' => $this->documentId,
                'phone' => $this->phone,
                'email' => $this->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send contract notification', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendContractNotification job failed permanently', [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
