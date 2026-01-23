<?php

namespace App\Jobs;

use App\Services\ReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyReconciliation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        protected int $year,
        protected int $month
    ) {}

    public function handle(ReconciliationService $reconciliationService): void
    {
        Log::info('Processando conciliação mensal', [
            'year' => $this->year,
            'month' => $this->month,
        ]);

        try {
            $reconciliation = $reconciliationService->processMonthlyReconciliation(
                $this->year,
                $this->month
            );

            Log::info('Conciliação processada', [
                'period' => $reconciliation->period,
                'balance' => $reconciliation->balance,
                'discrepancy' => $reconciliation->discrepancy_amount,
            ]);

            // Alerta se houver discrepância significativa
            if ($reconciliation->hasDiscrepancy()) {
                Log::warning('Discrepância detectada na conciliação', [
                    'period' => $reconciliation->period,
                    'amount' => $reconciliation->discrepancy_amount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro na conciliação', [
                'year' => $this->year,
                'month' => $this->month,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
