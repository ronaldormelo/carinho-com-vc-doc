<?php

namespace App\Jobs;

use App\Models\Emergency;
use App\Services\EmergencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processamento de alertas de emergencia.
 */
class ProcessEmergencyAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 3;

    /**
     * Tempo de backoff entre tentativas (segundos).
     */
    public array $backoff = [5, 10, 20];

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        public Emergency $emergency
    ) {
        $this->onQueue('emergencies');
    }

    /**
     * Executa o job.
     */
    public function handle(EmergencyService $emergencyService): void
    {
        Log::info('Processando alerta de emergencia', [
            'emergency_id' => $this->emergency->id,
            'severity' => $this->emergency->severity_id,
        ]);

        $emergencyService->processEmergencyAlert($this->emergency);

        Log::info('Alerta de emergencia processado', [
            'emergency_id' => $this->emergency->id,
        ]);
    }

    /**
     * Trata falha do job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de emergencia falhou', [
            'emergency_id' => $this->emergency->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
