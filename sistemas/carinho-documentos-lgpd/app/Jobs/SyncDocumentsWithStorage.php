<?php

namespace App\Jobs;

use App\Integrations\Storage\S3StorageClient;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizar metadados de documentos com o storage.
 */
class SyncDocumentsWithStorage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800; // 30 minutos

    public function handle(S3StorageClient $storage): void
    {
        Log::info('Starting documents sync with storage');

        $synced = 0;
        $missing = 0;

        // Verifica as ultimas versoes de documentos criados na ultima hora
        DocumentVersion::where('created_at', '>=', now()->subHour())
            ->chunk(100, function ($versions) use ($storage, &$synced, &$missing) {
                foreach ($versions as $version) {
                    try {
                        $exists = $storage->exists($version->file_url);

                        if (!$exists) {
                            Log::warning('Document version file missing in storage', [
                                'version_id' => $version->id,
                                'document_id' => $version->document_id,
                                'file_url' => $version->file_url,
                            ]);
                            $missing++;
                        } else {
                            // Verifica integridade via metadados
                            $metadata = $storage->getMetadata($version->file_url);

                            if ($metadata['ok'] && isset($metadata['metadata']['checksum'])) {
                                if ($metadata['metadata']['checksum'] !== $version->checksum) {
                                    Log::warning('Document version checksum mismatch', [
                                        'version_id' => $version->id,
                                        'stored_checksum' => $metadata['metadata']['checksum'],
                                        'db_checksum' => $version->checksum,
                                    ]);
                                }
                            }

                            $synced++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to sync document version', [
                            'version_id' => $version->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Documents sync completed', [
            'synced' => $synced,
            'missing' => $missing,
        ]);
    }
}
