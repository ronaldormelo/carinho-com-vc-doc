<?php

namespace App\Jobs;

use App\Services\Integrations\Crm\CrmClient;
use App\Services\Integrations\Operacao\OperacaoClient;
use App\Services\Integrations\Financeiro\FinanceiroClient;
use App\Services\Integrations\Cuidadores\CuidadoresClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processamento de servico finalizado.
 *
 * Fluxo: Servico concluido -> Feedback + Faturamento + Calculo de repasse
 */
class ProcessServiceCompleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        private array $serviceData
    ) {
        $this->onQueue('integrations');
    }

    /**
     * Executa o job.
     */
    public function handle(
        CrmClient $crm,
        OperacaoClient $operacao,
        FinanceiroClient $financeiro,
        CuidadoresClient $cuidadores
    ): void {
        Log::info('Processing completed service', [
            'service_id' => $this->serviceData['id'],
            'client_id' => $this->serviceData['client_id'],
        ]);

        // 1. Notifica cliente da finalizacao
        if (!empty($this->serviceData['client_phone'])) {
            SendWhatsAppMessage::dispatch('service_completed', [
                'phone' => $this->serviceData['client_phone'],
                'name' => $this->serviceData['client_name'],
            ]);

            Log::info('Service completion notification sent', [
                'phone' => $this->serviceData['client_phone'],
            ]);
        }

        // 2. Solicita feedback apos delay (2 horas)
        SendWhatsAppMessage::dispatch('feedback_request', [
            'phone' => $this->serviceData['client_phone'],
            'client_name' => $this->serviceData['client_name'],
            'caregiver_name' => $this->serviceData['caregiver_name'],
        ])->delay(now()->addHours(2));

        // 3. Solicita feedback por email tambem
        if (!empty($this->serviceData['client_email'])) {
            SendEmail::dispatch('feedback_request', [
                'email' => $this->serviceData['client_email'],
                'client_name' => $this->serviceData['client_name'],
                'caregiver_name' => $this->serviceData['caregiver_name'],
                'feedback_link' => $this->generateFeedbackLink(),
            ])->delay(now()->addHours(2));
        }

        // 4. Registra servico concluido no CRM
        $crm->dispatchEvent('service.completed', [
            'service_id' => $this->serviceData['id'],
            'client_id' => $this->serviceData['client_id'],
            'caregiver_id' => $this->serviceData['caregiver_id'],
            'completed_at' => $this->serviceData['completed_at'] ?? now()->toIso8601String(),
            'duration_hours' => $this->serviceData['duration_hours'] ?? 0,
        ]);

        // 5. Cria fatura no financeiro (se nao for recorrente)
        if (!($this->serviceData['is_recurring'] ?? false)) {
            $priceResult = $financeiro->calculatePrice([
                'service_type' => $this->serviceData['service_type'],
                'hours' => $this->serviceData['duration_hours'],
                'is_weekend' => $this->serviceData['is_weekend'] ?? false,
                'is_holiday' => $this->serviceData['is_holiday'] ?? false,
                'is_night' => $this->serviceData['is_night'] ?? false,
            ]);

            if ($priceResult['ok']) {
                $financeiro->createInvoice([
                    'client_id' => $this->serviceData['client_id'],
                    'operacao_service_id' => $this->serviceData['id'],
                    'amount' => $priceResult['body']['total'] ?? 0,
                    'description' => "Serviço de cuidador - {$this->serviceData['service_type']}",
                    'due_date' => now()->addDays(3)->format('Y-m-d'),
                ]);
            }
        }

        // 6. Atualiza estatisticas do cuidador
        $cuidadores->dispatchEvent('service.completed', [
            'caregiver_id' => $this->serviceData['caregiver_id'],
            'service_id' => $this->serviceData['id'],
            'duration_hours' => $this->serviceData['duration_hours'],
            'completed_at' => $this->serviceData['completed_at'] ?? now()->toIso8601String(),
        ]);

        Log::info('Service completion processing completed', [
            'service_id' => $this->serviceData['id'],
        ]);
    }

    /**
     * Gera link para feedback.
     */
    private function generateFeedbackLink(): string
    {
        $token = base64_encode(json_encode([
            'service_id' => $this->serviceData['id'],
            'client_id' => $this->serviceData['client_id'],
            'expires' => now()->addDays(7)->timestamp,
        ]));

        return config('app.url') . "/feedback/{$token}";
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Service completion processing failed', [
            'service_id' => $this->serviceData['id'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return [
            'service',
            'service_completed',
            'service_id:' . ($this->serviceData['id'] ?? 'unknown'),
        ];
    }
}
