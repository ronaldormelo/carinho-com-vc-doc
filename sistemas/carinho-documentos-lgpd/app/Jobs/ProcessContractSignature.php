<?php

namespace App\Jobs;

use App\Integrations\Storage\S3StorageClient;
use App\Models\Document;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processar assinatura de contrato.
 */
class ProcessContractSignature implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private int $documentId,
        private string $phone,
        private string $email,
        private string $recipientName
    ) {}

    public function handle(
        S3StorageClient $storage,
        NotificationService $notification
    ): void {
        try {
            $document = Document::with(['template.docType', 'versions'])->find($this->documentId);

            if (!$document) {
                Log::warning('Document not found for signature processing', [
                    'document_id' => $this->documentId,
                ]);

                return;
            }

            $latestVersion = $document->latestVersion();
            if (!$latestVersion) {
                Log::warning('No version found for document', [
                    'document_id' => $this->documentId,
                ]);

                return;
            }

            // Gera URL assinada para download
            $urlResult = $storage->getSignedUrl(
                $latestVersion->file_url,
                config('documentos.signed_urls.contract_expiration', 1440)
            );

            if (!$urlResult['ok']) {
                throw new \Exception('Falha ao gerar URL assinada');
            }

            // Envia notificacao de contrato assinado
            $notification->notifyContractSigned(
                $this->phone,
                $this->email,
                $document->template?->docType?->label ?? 'Contrato',
                $urlResult['url']
            );

            Log::info('Contract signature processed', [
                'document_id' => $this->documentId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to process contract signature', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessContractSignature job failed permanently', [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
