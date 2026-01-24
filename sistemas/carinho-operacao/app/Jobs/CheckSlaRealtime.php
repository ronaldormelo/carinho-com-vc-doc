<?php

namespace App\Jobs;

use App\Services\SlaService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para verificação de SLA em tempo real.
 * 
 * Executa a cada 5 minutos para detectar violações
 * e gerar alertas proativos para a operação.
 */
class CheckSlaRealtime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cria nova instância do job.
     */
    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    /**
     * Executa o job.
     */
    public function handle(SlaService $slaService): void
    {
        Log::info('Verificando SLA em tempo real');

        try {
            $alerts = $slaService->checkRealTimeSla();

            if (count($alerts) > 0) {
                Log::warning('Alertas de SLA em tempo real', [
                    'count' => count($alerts),
                    'alerts' => $alerts,
                ]);

                // Notifica supervisores sobre alertas críticos
                $criticalAlerts = collect($alerts)->where('severity', 'critical');
                
                if ($criticalAlerts->count() > 0) {
                    $this->notifySupervisors($criticalAlerts->toArray());
                }
            }

            Log::info('Verificação de SLA em tempo real concluída', [
                'alerts_found' => count($alerts),
            ]);

        } catch (\Throwable $e) {
            Log::error('Erro na verificação de SLA em tempo real', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Notifica supervisores sobre alertas críticos.
     */
    protected function notifySupervisors(array $criticalAlerts): void
    {
        // Em produção, enviaria email ou notificação para supervisores
        $alertEmail = config('operacao.emergency.alert_email');

        if (!$alertEmail) {
            return;
        }

        Log::info('Notificando supervisores sobre alertas críticos de SLA', [
            'alert_count' => count($criticalAlerts),
            'email' => $alertEmail,
        ]);

        // Mail::to($alertEmail)->send(new SlaCriticalAlert($criticalAlerts));
    }
}
