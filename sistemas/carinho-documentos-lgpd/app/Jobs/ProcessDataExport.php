<?php

namespace App\Jobs;

use App\Models\DataRequest;
use App\Services\LgpdService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processar exportacao de dados LGPD.
 */
class ProcessDataExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 600; // 10 minutos

    public function __construct(
        private int $requestId,
        private ?string $phone = null
    ) {}

    public function handle(
        LgpdService $lgpdService,
        NotificationService $notification
    ): void {
        try {
            $result = $lgpdService->processExportRequest($this->requestId);

            if (!$result['ok']) {
                throw new \Exception($result['error'] ?? 'Falha na exportacao');
            }

            // Notifica titular se tiver telefone
            if ($this->phone) {
                $lgpdService->notifyRequestStatus($this->requestId, $this->phone);
            }

            Log::info('Data export processed', [
                'request_id' => $this->requestId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to process data export', [
                'request_id' => $this->requestId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDataExport job failed permanently', [
            'request_id' => $this->requestId,
            'error' => $exception->getMessage(),
        ]);
    }
}
