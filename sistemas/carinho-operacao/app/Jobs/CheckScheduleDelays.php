<?php

namespace App\Jobs;

use App\Services\CheckinService;
use App\Services\SubstitutionService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para verificacao periodica de atrasos em agendamentos.
 */
class CheckScheduleDelays implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cria nova instancia do job.
     */
    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    /**
     * Executa o job.
     */
    public function handle(
        CheckinService $checkinService,
        SubstitutionService $substitutionService,
        NotificationService $notificationService
    ): void {
        Log::info('Verificando atrasos em agendamentos');

        // Verifica atrasos
        $delays = $checkinService->checkDelays();

        foreach ($delays as $delay) {
            $schedule = $delay['schedule'];
            $delayMinutes = $delay['delay_minutes'];

            Log::warning('Atraso detectado em agendamento', [
                'schedule_id' => $schedule->id,
                'delay_minutes' => $delayMinutes,
            ]);

            // Para atrasos criticos, verifica necessidade de substituicao
            $maxDelay = config('operacao.substitution.max_search_time_minutes', 120);
            if ($delayMinutes >= $maxDelay) {
                Log::warning('Atraso critico - processando substituicao', [
                    'schedule_id' => $schedule->id,
                ]);

                // Processa no-show
                $substitutionService->processNoShow($schedule);
            }
        }

        Log::info('Verificacao de atrasos concluida', [
            'delays_found' => $delays->count(),
        ]);
    }
}
