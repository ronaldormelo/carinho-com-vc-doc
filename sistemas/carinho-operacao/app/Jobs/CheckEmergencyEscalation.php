<?php

namespace App\Jobs;

use App\Services\EmergencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para verificacao de escalonamento de emergencias.
 */
class CheckEmergencyEscalation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cria nova instancia do job.
     */
    public function __construct()
    {
        $this->onQueue('emergencies');
    }

    /**
     * Executa o job.
     */
    public function handle(EmergencyService $emergencyService): void
    {
        Log::info('Verificando emergencias para escalonamento');

        $emergencies = $emergencyService->getEmergenciesNeedingEscalation();

        foreach ($emergencies as $emergency) {
            Log::warning('Escalonando emergencia', [
                'emergency_id' => $emergency->id,
                'current_severity' => $emergency->severity_id,
            ]);

            $emergencyService->escalateEmergency($emergency);
        }

        Log::info('Verificacao de escalonamento concluida', [
            'escalated' => $emergencies->count(),
        ]);
    }
}
