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
 * Job para limpar documentos expirados.
 */
class CleanExpiredDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600; // 1 hora

    /**
     * Tempo adicional apos retencao para exclusao permanente (em dias).
     */
    private const DELETION_GRACE_PERIOD = 365; // 1 ano apos arquivamento

    public function handle(S3StorageClient $storage): void
    {
        Log::info('Starting expired documents cleanup');

        $totalDeleted = 0;

        // Busca documentos arquivados ha mais de 1 ano
        $deletionDate = now()->subDays(self::DELETION_GRACE_PERIOD);

        Document::where('status_id', DomainDocumentStatus::ARCHIVED)
            ->where('updated_at', '<', $deletionDate)
            ->with('versions')
            ->chunk(50, function ($documents) use ($storage, &$totalDeleted) {
                foreach ($documents as $document) {
                    try {
                        DB::transaction(function () use ($document, $storage, &$totalDeleted) {
                            // Exclui arquivos do S3
                            foreach ($document->versions as $version) {
                                $storage->delete($version->file_url);
                            }

                            // Exclui documento
                            $document->delete();
                            $totalDeleted++;
                        });
                    } catch (\Throwable $e) {
                        Log::warning('Failed to delete expired document', [
                            'document_id' => $document->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Expired documents cleanup completed', [
            'total_deleted' => $totalDeleted,
        ]);
    }
}
