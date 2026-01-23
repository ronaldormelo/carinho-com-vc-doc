<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        protected array $event
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        $type = $this->event['type'] ?? '';
        $data = $this->event['data']['object'] ?? [];

        Log::info('Processando webhook Stripe', [
            'type' => $type,
            'id' => $data['id'] ?? null,
        ]);

        switch ($type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($paymentService, $data);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($paymentService, $data);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($data);
                break;

            case 'payout.paid':
                $this->handlePayoutPaid($data);
                break;

            case 'account.updated':
                $this->handleAccountUpdated($data);
                break;

            default:
                Log::debug('Evento Stripe não tratado', ['type' => $type]);
        }
    }

    protected function handlePaymentSucceeded(PaymentService $paymentService, array $data): void
    {
        $paymentIntentId = $data['id'];
        $chargeId = $data['latest_charge'] ?? null;

        try {
            $paymentService->confirmPayment($paymentIntentId, [
                'charge_id' => $chargeId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            Log::info('Pagamento confirmado via webhook', [
                'payment_intent_id' => $paymentIntentId,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao confirmar pagamento', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function handlePaymentFailed(PaymentService $paymentService, array $data): void
    {
        $paymentIntentId = $data['id'];
        $reason = $data['last_payment_error']['message'] ?? 'Falha no pagamento';

        try {
            $paymentService->failPayment($paymentIntentId, $reason);

            Log::info('Pagamento marcado como falhou', [
                'payment_intent_id' => $paymentIntentId,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar falha de pagamento', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function handleChargeRefunded(array $data): void
    {
        // Reembolso já processado internamente, apenas log
        Log::info('Reembolso confirmado pelo Stripe', [
            'charge_id' => $data['id'],
            'amount_refunded' => ($data['amount_refunded'] ?? 0) / 100,
        ]);
    }

    protected function handlePayoutPaid(array $data): void
    {
        // Repasse via Stripe Connect foi pago
        Log::info('Payout Stripe Connect confirmado', [
            'payout_id' => $data['id'],
            'amount' => ($data['amount'] ?? 0) / 100,
        ]);
    }

    protected function handleAccountUpdated(array $data): void
    {
        // Conta Connect foi atualizada
        $accountId = $data['id'];
        $chargesEnabled = $data['charges_enabled'] ?? false;
        $payoutsEnabled = $data['payouts_enabled'] ?? false;

        Log::info('Conta Connect atualizada', [
            'account_id' => $accountId,
            'charges_enabled' => $chargesEnabled,
            'payouts_enabled' => $payoutsEnabled,
        ]);

        // Aqui poderia notificar sistema de cuidadores sobre status da conta
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falha ao processar webhook Stripe', [
            'event_type' => $this->event['type'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
