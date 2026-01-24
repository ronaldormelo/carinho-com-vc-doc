<?php

namespace App\Jobs;

use App\Services\SlaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para cálculo de métricas de SLA.
 * 
 * Executa diariamente para calcular indicadores operacionais
 * e detectar violações de SLA conforme práticas de gestão.
 */
class CalculateSlaMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $targetDate;

    /**
     * Cria nova instância do job.
     */
    public function __construct(?string $date = null)
    {
        $this->targetDate = $date;
        $this->onQueue('metrics');
    }

    /**
     * Executa o job.
     */
    public function handle(SlaService $slaService): void
    {
        Log::info('Iniciando cálculo de métricas de SLA', [
            'date' => $this->targetDate ?? 'yesterday',
        ]);

        try {
            $metrics = $slaService->calculateDailyMetrics($this->targetDate);

            $outOfSla = $metrics->where('target_met', false)->count();

            Log::info('Métricas de SLA calculadas com sucesso', [
                'total_metrics' => $metrics->count(),
                'out_of_sla' => $outOfSla,
            ]);

            // Se houver métricas fora do SLA, loga alerta
            if ($outOfSla > 0) {
                Log::warning('Métricas fora do SLA detectadas', [
                    'count' => $outOfSla,
                    'metrics' => $metrics->where('target_met', false)
                        ->pluck('metric_type')
                        ->toArray(),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Erro ao calcular métricas de SLA', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
