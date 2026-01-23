<?php

namespace App\Jobs;

use App\Integrations\Storage\S3StorageClient;
use App\Models\Document;
use App\Models\DomainDocumentStatus;
use App\Models\RetentionPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job para executar politicas de retencao.
 */
class ProcessRetentionPolicies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600; // 1 hora

    public function handle(S3StorageClient $storage): void
    {
        Log::info('Starting retention policies processing');

        $policies = RetentionPolicy::with('docType')->get();
        $totalArchived = 0;
        $totalDeleted = 0;

        foreach ($policies as $policy) {
            try {
                $result = $this->processPolicy($policy, $storage);
                $totalArchived += $result['archived'];
                $totalDeleted += $result['deleted'];
            } catch (\Throwable $e) {
                Log::error('Error processing retention policy', [
                    'policy_id' => $policy->id,
                    'doc_type' => $policy->docType->code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Retention policies processing completed', [
            'total_archived' => $totalArchived,
            'total_deleted' => $totalDeleted,
        ]);
    }

    private function processPolicy(RetentionPolicy $policy, S3StorageClient $storage): array
    {
        $expirationDate = now()->subDays($policy->retention_days);
        $archived = 0;
        $deleted = 0;

        // Busca documentos expirados nao arquivados
        $documents = Document::whereHas('template', function ($query) use ($policy) {
            $query->where('doc_type_id', $policy->doc_type_id);
        })
            ->where('created_at', '<', $expirationDate)
            ->where('status_id', '!=', DomainDocumentStatus::ARCHIVED)
            ->with('versions')
            ->chunk(100, function ($docs) use ($storage, &$archived, &$deleted) {
                foreach ($docs as $document) {
                    try {
                        DB::transaction(function () use ($document, $storage, &$archived, &$deleted) {
                            // Arquiva documento
                            $document->status_id = DomainDocumentStatus::ARCHIVED;
                            $document->save();
                            $archived++;

                            // Opcional: mover para storage de arquivamento ou excluir
                            // Por padrao apenas arquiva, nao exclui
                        });
                    } catch (\Throwable $e) {
                        Log::warning('Failed to archive document', [
                            'document_id' => $document->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Policy processed', [
            'doc_type' => $policy->docType->code,
            'retention_days' => $policy->retention_days,
            'archived' => $archived,
            'deleted' => $deleted,
        ]);

        return [
            'archived' => $archived,
            'deleted' => $deleted,
        ];
    }
}
