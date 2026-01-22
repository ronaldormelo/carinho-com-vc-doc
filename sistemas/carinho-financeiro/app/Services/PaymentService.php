<?php

namespace App\Services;

use App\Events\PaymentConfirmed;
use App\Events\PaymentCreated;
use App\Events\PaymentFailed;
use App\Events\RefundProcessed;
use App\Integrations\Stripe\StripeClient;
use App\Models\DomainPaymentMethod;
use App\Models\DomainPaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Pagamentos.
 *
 * Responsável por:
 * - Criar e processar pagamentos via Stripe
 * - Gerenciar status de pagamentos
 * - Processar reembolsos
 * - Garantir idempotência
 */
class PaymentService
{
    public function __construct(
        protected StripeClient $stripeClient,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Cria um pagamento para uma fatura.
     */
    public function createPayment(Invoice $invoice, string $methodCode, ?array $metadata = null): Payment
    {
        if (!$invoice->canBePaid()) {
            throw new \Exception('Fatura não pode receber pagamento no status atual');
        }

        $method = DomainPaymentMethod::getByCode($methodCode);
        if (!$method) {
            throw new \Exception("Método de pagamento inválido: {$methodCode}");
        }

        // Gera chave de idempotência
        $idempotencyKey = Payment::generateIdempotencyKey();

        // Verifica se já existe pagamento pendente para esta fatura
        $existingPayment = Payment::where('invoice_id', $invoice->id)
            ->where('status_id', DomainPaymentStatus::PENDING)
            ->first();

        if ($existingPayment) {
            Log::info('Retornando pagamento existente', [
                'payment_id' => $existingPayment->id,
            ]);
            return $existingPayment;
        }

        return DB::transaction(function () use ($invoice, $method, $idempotencyKey, $metadata) {
            $amount = $invoice->total_with_fees;

            // Cria PaymentIntent no Stripe
            $stripeResult = $this->createStripePaymentIntent(
                $invoice,
                $method,
                $amount,
                $idempotencyKey,
                $metadata
            );

            // Cria registro de pagamento
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'method_id' => $method->id,
                'amount' => $amount,
                'status_id' => DomainPaymentStatus::PENDING,
                'idempotency_key' => $idempotencyKey,
                'stripe_payment_intent_id' => $stripeResult['payment_intent_id'] ?? null,
                'pix_code' => $stripeResult['pix_code'] ?? null,
                'pix_qrcode_url' => $stripeResult['pix_qrcode_url'] ?? null,
                'boleto_url' => $stripeResult['boleto_url'] ?? null,
                'boleto_barcode' => $stripeResult['boleto_barcode'] ?? null,
                'metadata' => $metadata,
            ]);

            Log::info('Pagamento criado', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'method' => $method->code,
                'amount' => $amount,
            ]);

            event(new PaymentCreated($payment));

            return $payment;
        });
    }

    /**
     * Cria PaymentIntent no Stripe.
     */
    protected function createStripePaymentIntent(
        Invoice $invoice,
        DomainPaymentMethod $method,
        float $amount,
        string $idempotencyKey,
        ?array $metadata
    ): array {
        $paymentMethodType = match ($method->id) {
            DomainPaymentMethod::PIX => 'pix',
            DomainPaymentMethod::BOLETO => 'boleto',
            DomainPaymentMethod::CARD => 'card',
            default => 'card',
        };

        $result = $this->stripeClient->createPaymentIntent([
            'amount' => $amount,
            'payment_method_type' => $paymentMethodType,
            'customer_id' => $this->getStripeCustomerId($invoice->client_id),
            'metadata' => array_merge([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'contract_id' => $invoice->contract_id,
            ], $metadata ?? []),
            'idempotency_key' => $idempotencyKey,
        ]);

        return $result;
    }

    /**
     * Confirma um pagamento (chamado via webhook do Stripe).
     */
    public function confirmPayment(string $stripePaymentIntentId, array $eventData = []): Payment
    {
        $payment = Payment::where('stripe_payment_intent_id', $stripePaymentIntentId)->first();

        if (!$payment) {
            throw new \Exception("Pagamento não encontrado: {$stripePaymentIntentId}");
        }

        // Idempotência: se já foi confirmado, retorna
        if ($payment->isPaid()) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $eventData) {
            $externalId = $eventData['charge_id'] ?? $eventData['payment_intent_id'] ?? null;
            
            $payment->markAsPaid($externalId);

            if (!empty($eventData['charge_id'])) {
                $payment->stripe_charge_id = $eventData['charge_id'];
                $payment->save();
            }

            // Atualiza fatura
            $invoice = $payment->invoice;
            $this->invoiceService->markAsPaid($invoice);

            Log::info('Pagamento confirmado', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'external_id' => $externalId,
            ]);

            event(new PaymentConfirmed($payment));

            return $payment;
        });
    }

    /**
     * Marca pagamento como falhou.
     */
    public function failPayment(string $stripePaymentIntentId, ?string $reason = null): Payment
    {
        $payment = Payment::where('stripe_payment_intent_id', $stripePaymentIntentId)->first();

        if (!$payment) {
            throw new \Exception("Pagamento não encontrado: {$stripePaymentIntentId}");
        }

        if (!$payment->isPending()) {
            return $payment;
        }

        $payment->markAsFailed($reason);

        Log::warning('Pagamento falhou', [
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]);

        event(new PaymentFailed($payment, $reason));

        return $payment;
    }

    /**
     * Processa reembolso de um pagamento.
     */
    public function refund(Payment $payment, float $amount, string $reason): Payment
    {
        if (!$payment->canBeRefunded()) {
            throw new \Exception('Este pagamento não pode ser reembolsado');
        }

        if ($amount > $payment->refundable_amount) {
            throw new \Exception('Valor do reembolso excede o disponível');
        }

        // Processa reembolso no Stripe
        $refundResult = $this->stripeClient->createRefund([
            'payment_intent_id' => $payment->stripe_payment_intent_id,
            'amount' => $amount,
            'reason' => $reason,
        ]);

        if (!$refundResult['success']) {
            throw new \Exception('Falha ao processar reembolso: ' . ($refundResult['error'] ?? 'Erro desconhecido'));
        }

        $payment->markAsRefunded($amount, $reason);

        Log::info('Reembolso processado', [
            'payment_id' => $payment->id,
            'amount' => $amount,
            'reason' => $reason,
        ]);

        event(new RefundProcessed($payment, $amount, $reason));

        return $payment;
    }

    /**
     * Obtém ID do cliente Stripe.
     */
    protected function getStripeCustomerId(int $clientId): ?string
    {
        $billingAccount = \App\Models\BillingAccount::where('client_id', $clientId)->first();
        
        if ($billingAccount && $billingAccount->stripe_customer_id) {
            return $billingAccount->stripe_customer_id;
        }

        // Cria cliente no Stripe se não existir
        // Isso seria integrado com o sistema CRM para obter dados do cliente
        return null;
    }

    /**
     * Obtém status de um pagamento pelo ID do Stripe.
     */
    public function getPaymentStatus(string $stripePaymentIntentId): array
    {
        return $this->stripeClient->getPaymentIntent($stripePaymentIntentId);
    }

    /**
     * Lista pagamentos de uma fatura.
     */
    public function getInvoicePayments(int $invoiceId): array
    {
        return Payment::where('invoice_id', $invoiceId)
            ->with(['method', 'status'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Gera link de pagamento para uma fatura.
     */
    public function generatePaymentLink(Invoice $invoice, string $methodCode = 'pix'): array
    {
        $payment = $this->createPayment($invoice, $methodCode);

        $link = match ($methodCode) {
            'pix' => [
                'type' => 'pix',
                'code' => $payment->pix_code,
                'qrcode_url' => $payment->pix_qrcode_url,
                'expires_at' => now()->addMinutes(config('integrations.stripe.pix.expires_after', 60)),
            ],
            'boleto' => [
                'type' => 'boleto',
                'url' => $payment->boleto_url,
                'barcode' => $payment->boleto_barcode,
                'expires_at' => now()->addDays(config('integrations.stripe.boleto.expires_after', 3)),
            ],
            default => [
                'type' => 'checkout',
                'url' => $this->stripeClient->createCheckoutSession($invoice, $payment),
            ],
        };

        return array_merge($link, [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);
    }
}
