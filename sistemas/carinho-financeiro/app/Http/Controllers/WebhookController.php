<?php

namespace App\Http\Controllers;

use App\Integrations\Stripe\StripeClient;
use App\Jobs\ProcessStripeWebhook;
use App\Jobs\SyncServiceToInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected StripeClient $stripeClient
    ) {}

    /**
     * Webhook do Stripe.
     *
     * Eventos tratados:
     * - payment_intent.succeeded: Pagamento confirmado
     * - payment_intent.payment_failed: Pagamento falhou
     * - charge.refunded: Reembolso processado
     * - payout.paid: Repasse enviado (Connect)
     * - account.updated: Conta Connect atualizada
     */
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Valida assinatura
        if (!$this->stripeClient->validateWebhookSignature($payload, $signature)) {
            Log::warning('Stripe webhook: assinatura inválida');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        Log::info('Stripe webhook recebido', [
            'type' => $event['type'] ?? 'unknown',
            'id' => $event['id'] ?? null,
        ]);

        // Processa de forma assíncrona
        ProcessStripeWebhook::dispatch($event);

        return response()->json(['received' => true]);
    }

    /**
     * Webhook interno de outros sistemas.
     *
     * Eventos tratados:
     * - service.completed: Serviço finalizado (do sistema Operação)
     * - contract.activated: Contrato ativado (do sistema CRM)
     * - caregiver.bank_updated: Dados bancários atualizados (do sistema Cuidadores)
     */
    public function internal(Request $request)
    {
        $request->validate([
            'event' => 'required|string',
            'payload' => 'required|array',
        ]);

        $event = $request->event;
        $payload = $request->payload;

        Log::info('Webhook interno recebido', [
            'event' => $event,
        ]);

        switch ($event) {
            case 'service.completed':
                // Serviço foi finalizado - pode ser faturado
                $this->handleServiceCompleted($payload);
                break;

            case 'contract.activated':
                // Contrato foi ativado - pode criar conta de cobrança
                $this->handleContractActivated($payload);
                break;

            case 'caregiver.bank_updated':
                // Dados bancários atualizados - sincroniza com Stripe
                $this->handleCaregiverBankUpdated($payload);
                break;

            default:
                Log::debug('Evento não tratado', ['event' => $event]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Trata serviço completado.
     */
    protected function handleServiceCompleted(array $payload): void
    {
        $serviceId = $payload['service_id'] ?? null;
        $clientId = $payload['client_id'] ?? null;
        $caregiverId = $payload['caregiver_id'] ?? null;

        if ($serviceId && $clientId && $caregiverId) {
            SyncServiceToInvoice::dispatch($serviceId, $clientId, $caregiverId);
        }
    }

    /**
     * Trata contrato ativado.
     */
    protected function handleContractActivated(array $payload): void
    {
        $clientId = $payload['client_id'] ?? null;
        $contractId = $payload['contract_id'] ?? null;

        if ($clientId && $contractId) {
            // Cria conta de cobrança para o cliente se não existir
            $exists = \App\Models\BillingAccount::where('client_id', $clientId)->exists();
            
            if (!$exists) {
                \App\Models\BillingAccount::create([
                    'client_id' => $clientId,
                    'payment_method_id' => \App\Models\DomainPaymentMethod::PIX,
                    'status_id' => \App\Models\DomainAccountStatus::ACTIVE,
                ]);

                Log::info('Conta de cobrança criada', ['client_id' => $clientId]);
            }
        }
    }

    /**
     * Trata atualização de dados bancários do cuidador.
     */
    protected function handleCaregiverBankUpdated(array $payload): void
    {
        $caregiverId = $payload['caregiver_id'] ?? null;
        $stripeAccountId = $payload['stripe_account_id'] ?? null;

        if ($caregiverId && $stripeAccountId) {
            // Atualiza conta bancária com ID do Stripe
            $bankAccount = \App\Models\BankAccount::forOwner(
                \App\Models\DomainOwnerType::CAREGIVER,
                $caregiverId
            )->default()->first();

            if ($bankAccount) {
                $bankAccount->stripe_external_account_id = $stripeAccountId;
                $bankAccount->save();

                Log::info('Conta Stripe vinculada', [
                    'caregiver_id' => $caregiverId,
                    'bank_account_id' => $bankAccount->id,
                ]);
            }
        }
    }
}
