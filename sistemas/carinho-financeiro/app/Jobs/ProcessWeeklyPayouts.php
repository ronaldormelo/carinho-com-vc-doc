<?php

namespace App\Jobs;

use App\Integrations\Cuidadores\CuidadoresClient;
use App\Services\NotificationService;
use App\Services\PayoutService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWeeklyPayouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutos

    public function handle(
        PayoutService $payoutService,
        NotificationService $notificationService,
        CuidadoresClient $cuidadoresClient
    ): void {
        Log::info('Iniciando processamento de repasses semanais');

        // Define período (semana anterior)
        $periodEnd = now()->startOfWeek()->subDay();
        $periodStart = $periodEnd->copy()->subDays(6);

        // Gera repasses para o período
        $generated = $payoutService->generatePayoutsForPeriod($periodStart, $periodEnd);

        Log::info('Repasses gerados', [
            'created' => $generated['total_created'],
            'failed' => $generated['total_failed'],
        ]);

        // Verifica se deve processar imediatamente
        $payoutDay = config('financeiro.payout.day_of_week', 5);
        
        if (now()->dayOfWeekIso !== $payoutDay) {
            Log::info('Hoje não é dia de repasse', [
                'today' => now()->dayOfWeekIso,
                'payout_day' => $payoutDay,
            ]);
            return;
        }

        // Processa todos os repasses pendentes
        $processed = $payoutService->processAllPendingPayouts();

        Log::info('Repasses processados', [
            'processed' => $processed['total_processed'],
            'failed' => $processed['total_failed'],
        ]);

        // Notifica cuidadores sobre repasses processados
        foreach ($processed['processed'] as $payoutId) {
            try {
                $payout = \App\Models\Payout::find($payoutId);
                
                if ($payout) {
                    $phone = $cuidadoresClient->getCaregiverPhone($payout->caregiver_id);
                    
                    if ($phone) {
                        $notificationService->notifyPayoutProcessed($payout, $phone);
                    }

                    // Notifica sistema de cuidadores
                    $cuidadoresClient->notifyPayoutProcessed(
                        $payout->caregiver_id,
                        $payout->id,
                        $payout->net_amount
                    );
                }
            } catch (\Exception $e) {
                Log::error('Erro ao notificar repasse', [
                    'payout_id' => $payoutId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
