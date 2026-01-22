<?php

namespace App\Jobs;

use App\Models\CaregiverDocument;
use App\Services\DocumentValidationService;
use App\Integrations\Integracoes\IntegracoesClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentValidation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 120;
    public int $timeout = 180;

    public function __construct(
        private CaregiverDocument $document
    ) {
        $this->onQueue('documents');
    }

    public function handle(
        DocumentValidationService $validationService,
        IntegracoesClient $integracoesClient
    ): void {
        Log::info('Processando validacao de documento', [
            'document_id' => $this->document->id,
            'caregiver_id' => $this->document->caregiver_id,
        ]);

        // Publica evento de upload
        $integracoesClient->documentUploaded(
            $this->document->caregiver_id,
            $this->document->id,
            $this->document->docType?->code ?? 'unknown'
        );

        // Executa validacao
        $result = $validationService->validateDocument($this->document);

        if ($result['success']) {
            // Publica evento de verificacao
            $integracoesClient->documentVerified(
                $this->document->caregiver_id,
                $this->document->id,
                $this->document->docType?->code ?? 'unknown'
            );

            Log::info('Documento validado automaticamente', [
                'document_id' => $this->document->id,
            ]);
        } elseif ($result['needs_manual_review'] ?? false) {
            Log::info('Documento requer revisao manual', [
                'document_id' => $this->document->id,
            ]);
        } else {
            Log::warning('Documento rejeitado na validacao', [
                'document_id' => $this->document->id,
                'message' => $result['message'] ?? '',
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job de validacao de documento falhou', [
            'document_id' => $this->document->id,
            'caregiver_id' => $this->document->caregiver_id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'document-validation',
            'document:' . $this->document->id,
            'caregiver:' . $this->document->caregiver_id,
        ];
    }
}
