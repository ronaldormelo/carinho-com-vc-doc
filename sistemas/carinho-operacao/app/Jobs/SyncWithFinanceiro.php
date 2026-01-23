<?php

namespace App\Jobs;

use App\Models\Schedule;
use App\Integrations\Financeiro\FinanceiroClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizacao de horas trabalhadas com o Financeiro.
 */
class SyncWithFinanceiro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 3;

    /**
     * Tempo de backoff entre tentativas (segundos).
     */
    public array $backoff = [10, 30, 60];

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        public Schedule $schedule
    ) {
        $this->onQueue('financeiro');
    }

    /**
     * Executa o job.
     */
    public function handle(FinanceiroClient $financeiroClient): void
    {
        $schedule = $this->schedule->fresh(['checkins', 'assignment']);

        if (!$schedule->isDone()) {
            Log::warning('Agendamento nao concluido - ignorando sync com Financeiro', [
                'schedule_id' => $schedule->id,
            ]);
            return;
        }

        Log::info('Sincronizando horas com Financeiro', [
            'schedule_id' => $schedule->id,
        ]);

        $checkin = $schedule->checkin;
        $checkout = $schedule->checkout;

        if (!$checkin || !$checkout) {
            Log::warning('Check-in ou checkout ausente', [
                'schedule_id' => $schedule->id,
            ]);
            return;
        }

        $totalHours = $checkin->timestamp->diffInMinutes($checkout->timestamp) / 60;

        $result = $financeiroClient->registerWorkedHours([
            'schedule_id' => $schedule->id,
            'caregiver_id' => $schedule->caregiver_id,
            'client_id' => $schedule->client_id,
            'shift_date' => $schedule->shift_date->toDateString(),
            'check_in' => $checkin->timestamp->toIso8601String(),
            'check_out' => $checkout->timestamp->toIso8601String(),
            'total_hours' => round($totalHours, 2),
        ]);

        if (!$result['ok']) {
            throw new \RuntimeException('Falha ao sincronizar com Financeiro: ' . ($result['error'] ?? 'Unknown error'));
        }

        Log::info('Horas sincronizadas com Financeiro', [
            'schedule_id' => $schedule->id,
            'total_hours' => round($totalHours, 2),
        ]);
    }

    /**
     * Trata falha do job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de sync com Financeiro falhou', [
            'schedule_id' => $this->schedule->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
