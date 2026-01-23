<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Job para entrega de webhooks.
 *
 * Envia payload transformado para endpoint de sistema alvo
 * com assinatura HMAC e retry em caso de falha.
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas antes de falhar.
     */
    public int $tries = 5;

    /**
     * Tempo maximo de execucao em segundos.
     */
    public int $timeout = 30;

    /**
     * Backoff exponencial em segundos.
     */
    public array $backoff = [10, 30, 60, 120, 300];

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        public WebhookDelivery $delivery,
        public WebhookEndpoint $endpoint,
        public array $payload
    ) {
        $this->onQueue('integrations-high');
    }

    /**
     * Executa o job.
     */
    public function handle(): void
    {
        Log::info('Delivering webhook', [
            'delivery_id' => $this->delivery->id,
            'endpoint' => $this->endpoint->url,
            'system' => $this->endpoint->system_name,
        ]);

        try {
            $payloadJson = json_encode($this->payload);
            $signature = $this->endpoint->generateSignature($payloadJson);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Source-System' => 'integracoes',
                'X-Event-Type' => $this->payload['_meta']['event_type'] ?? 'unknown',
                'X-Event-ID' => $this->payload['_meta']['event_id'] ?? '',
                'X-Timestamp' => $this->payload['_meta']['timestamp'] ?? now()->toIso8601String(),
            ])
                ->timeout(15)
                ->connectTimeout(5)
                ->post($this->endpoint->url, $this->payload);

            $statusCode = $response->status();
            $success = $response->successful();

            $this->delivery->recordAttempt($statusCode, $success);

            if ($success) {
                Log::info('Webhook delivered successfully', [
                    'delivery_id' => $this->delivery->id,
                    'status' => $statusCode,
                ]);
            } else {
                Log::warning('Webhook delivery failed', [
                    'delivery_id' => $this->delivery->id,
                    'status' => $statusCode,
                    'response' => $response->body(),
                ]);

                // Se ainda pode tentar, relanca o job
                if ($this->delivery->shouldRetry()) {
                    $this->release($this->backoff[$this->attempts() - 1] ?? 300);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Webhook delivery error', [
                'delivery_id' => $this->delivery->id,
                'error' => $e->getMessage(),
            ]);

            $this->delivery->recordAttempt(0, false);

            if ($this->delivery->shouldRetry()) {
                throw $e; // Permite retry automatico
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook delivery failed permanently', [
            'delivery_id' => $this->delivery->id,
            'endpoint' => $this->endpoint->url,
            'error' => $exception->getMessage(),
        ]);

        $this->delivery->markAsFailed();
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return [
            'webhook',
            'system:' . $this->endpoint->system_name,
            'delivery_id:' . $this->delivery->id,
        ];
    }
}
