<?php

namespace App\Jobs;

use App\Services\ScheduleService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para envio de lembretes de agendamento.
 */
class SendScheduleReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        public int $hoursAhead = 24
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Executa o job.
     */
    public function handle(
        ScheduleService $scheduleService,
        NotificationService $notificationService
    ): void {
        Log::info('Enviando lembretes de agendamento', [
            'hours_ahead' => $this->hoursAhead,
        ]);

        $schedules = $scheduleService->getSchedulesNeedingReminder($this->hoursAhead);

        $sentCount = 0;
        foreach ($schedules as $schedule) {
            try {
                $notificationService->sendScheduleReminder($schedule);
                $sentCount++;
            } catch (\Throwable $e) {
                Log::error('Erro ao enviar lembrete', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Lembretes enviados', [
            'sent' => $sentCount,
            'total' => $schedules->count(),
        ]);
    }
}
