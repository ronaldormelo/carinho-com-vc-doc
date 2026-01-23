<?php

namespace App\Jobs;

use App\Models\DataRequest;
use App\Services\LgpdService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processar exclusao de dados LGPD.
 */
class ProcessDataDeletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 600; // 10 minutos

    public function __construct(
        private int $requestId,
        private ?string $phone = null
    ) {}

    public function handle(LgpdService $lgpdService): void
    {
        try {
            $result = $lgpdService->processDeleteRequest($this->requestId);

            if (!$result['ok']) {
                throw new \Exception($result['error'] ?? 'Falha na exclusao');
            }

            // Notifica titular se tiver telefone
            if ($this->phone) {
                $lgpdService->notifyRequestStatus($this->requestId, $this->phone);
            }

            Log::info('Data deletion processed', [
                'request_id' => $this->requestId,
                'documents_deleted' => $result['documents_deleted'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to process data deletion', [
                'request_id' => $this->requestId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDataDeletion job failed permanently', [
            'request_id' => $this->requestId,
            'error' => $exception->getMessage(),
        ]);
    }
}
