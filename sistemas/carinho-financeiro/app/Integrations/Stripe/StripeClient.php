<?php

namespace App\Integrations\Stripe;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integração com Stripe Payments.
 *
 * Documentação: https://stripe.com/docs/api
 *
 * Funcionalidades implementadas:
 * - PaymentIntent (PIX, Boleto, Cartão)
 * - Refunds
 * - Stripe Connect (Transfers/Payouts para cuidadores)
 * - Customers
 * - Webhooks
 *
 * Fluxo de Pagamento:
 * 1. Criar PaymentIntent com método desejado
 * 2. Cliente completa pagamento (PIX/Boleto/Cartão)
 * 3. Webhook notifica confirmação
 * 4. Sistema atualiza status do pagamento
 *
 * Fluxo de Repasse (Connect):
 * 1. Cuidador cria conta Connect (Express)
 * 2. Vincula conta bancária
 * 3. Sistema cria Transfer quando repasse é aprovado
 * 4. Stripe processa payout para conta do cuidador
 */
class StripeClient
{
    protected string $baseUrl = 'https://api.stripe.com/v1';
    protected string $secretKey;
    protected string $currency;
    protected int $timeout;

    public function __construct()
    {
        $this->secretKey = config('integrations.stripe.secret_key', '');
        $this->currency = config('integrations.stripe.currency', 'brl');
        $this->timeout = config('integrations.stripe.timeout', 30);
    }

    /**
     * Cria PaymentIntent para cobrança.
     *
     * @param array $params [
     *     'amount' => float (valor em reais),
     *     'payment_method_type' => string (pix|boleto|card),
     *     'customer_id' => string|null,
     *     'metadata' => array,
     *     'idempotency_key' => string,
     * ]
     */
    public function createPaymentIntent(array $params): array
    {
        // Converte para centavos (Stripe usa menor unidade)
        $amountInCents = (int) round($params['amount'] * 100);

        $paymentMethodTypes = $this->getPaymentMethodTypes($params['payment_method_type'] ?? 'pix');

        $data = [
            'amount' => $amountInCents,
            'currency' => $this->currency,
            'payment_method_types' => $paymentMethodTypes,
            'metadata' => $params['metadata'] ?? [],
        ];

        // Adiciona customer se fornecido
        if (!empty($params['customer_id'])) {
            $data['customer'] = $params['customer_id'];
        }

        // Configurações específicas por método
        if ($params['payment_method_type'] === 'pix') {
            $expiresIn = config('integrations.stripe.pix.expires_after', 60) * 60; // segundos
            $data['payment_method_options'] = [
                'pix' => [
                    'expires_after_seconds' => $expiresIn,
                ],
            ];
        }

        if ($params['payment_method_type'] === 'boleto') {
            $expiresIn = config('integrations.stripe.boleto.expires_after', 3);
            $data['payment_method_options'] = [
                'boleto' => [
                    'expires_after_days' => $expiresIn,
                ],
            ];
        }

        $response = $this->request('POST', '/payment_intents', $data, [
            'Idempotency-Key' => $params['idempotency_key'] ?? null,
        ]);

        if (!$response['success']) {
            return $response;
        }

        $paymentIntent = $response['data'];

        // Extrai dados específicos por método
        $result = [
            'success' => true,
            'payment_intent_id' => $paymentIntent['id'],
            'status' => $paymentIntent['status'],
            'client_secret' => $paymentIntent['client_secret'],
        ];

        // Para PIX, extrai código e QR code
        if ($params['payment_method_type'] === 'pix' && isset($paymentIntent['next_action']['pix_display_qr_code'])) {
            $pixData = $paymentIntent['next_action']['pix_display_qr_code'];
            $result['pix_code'] = $pixData['data'] ?? null;
            $result['pix_qrcode_url'] = $pixData['image_url_png'] ?? null;
            $result['pix_expires_at'] = $pixData['expires_at'] ?? null;
        }

        // Para Boleto, extrai URL e código de barras
        if ($params['payment_method_type'] === 'boleto' && isset($paymentIntent['next_action']['boleto_display_details'])) {
            $boletoData = $paymentIntent['next_action']['boleto_display_details'];
            $result['boleto_url'] = $boletoData['hosted_voucher_url'] ?? null;
            $result['boleto_barcode'] = $boletoData['number'] ?? null;
            $result['boleto_expires_at'] = $boletoData['expires_at'] ?? null;
        }

        return $result;
    }

    /**
     * Obtém PaymentIntent existente.
     */
    public function getPaymentIntent(string $paymentIntentId): array
    {
        return $this->request('GET', "/payment_intents/{$paymentIntentId}");
    }

    /**
     * Confirma PaymentIntent (para cartão sem frontend).
     */
    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethodId): array
    {
        return $this->request('POST', "/payment_intents/{$paymentIntentId}/confirm", [
            'payment_method' => $paymentMethodId,
        ]);
    }

    /**
     * Cancela PaymentIntent.
     */
    public function cancelPaymentIntent(string $paymentIntentId): array
    {
        return $this->request('POST', "/payment_intents/{$paymentIntentId}/cancel");
    }

    /**
     * Cria reembolso.
     *
     * @param array $params [
     *     'payment_intent_id' => string,
     *     'amount' => float (valor em reais, opcional para reembolso parcial),
     *     'reason' => string,
     * ]
     */
    public function createRefund(array $params): array
    {
        $data = [
            'payment_intent' => $params['payment_intent_id'],
            'reason' => $this->mapRefundReason($params['reason'] ?? ''),
        ];

        // Se valor especificado, faz reembolso parcial
        if (!empty($params['amount'])) {
            $data['amount'] = (int) round($params['amount'] * 100);
        }

        $response = $this->request('POST', '/refunds', $data);

        if ($response['success']) {
            $refund = $response['data'];
            return [
                'success' => true,
                'refund_id' => $refund['id'],
                'amount' => $refund['amount'] / 100,
                'status' => $refund['status'],
            ];
        }

        return $response;
    }

    /**
     * Cria ou obtém cliente Stripe.
     */
    public function createCustomer(array $params): array
    {
        $data = [
            'email' => $params['email'] ?? null,
            'name' => $params['name'] ?? null,
            'phone' => $params['phone'] ?? null,
            'metadata' => $params['metadata'] ?? [],
        ];

        // Remove valores nulos
        $data = array_filter($data, fn ($v) => $v !== null);

        return $this->request('POST', '/customers', $data);
    }

    /**
     * Obtém cliente Stripe.
     */
    public function getCustomer(string $customerId): array
    {
        return $this->request('GET', "/customers/{$customerId}");
    }

    /*
    |--------------------------------------------------------------------------
    | Stripe Connect - Repasses para Cuidadores
    |--------------------------------------------------------------------------
    */

    /**
     * Cria link para onboarding de conta Connect (Express).
     */
    public function createConnectAccountLink(string $accountId, string $returnUrl, string $refreshUrl): array
    {
        return $this->request('POST', '/account_links', [
            'account' => $accountId,
            'return_url' => $returnUrl,
            'refresh_url' => $refreshUrl,
            'type' => 'account_onboarding',
        ]);
    }

    /**
     * Cria conta Connect para cuidador.
     */
    public function createConnectAccount(array $params): array
    {
        $data = [
            'type' => config('integrations.stripe.connect.account_type', 'express'),
            'country' => 'BR',
            'email' => $params['email'],
            'capabilities' => [
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
            'metadata' => $params['metadata'] ?? [],
        ];

        $response = $this->request('POST', '/accounts', $data);

        if ($response['success']) {
            return [
                'success' => true,
                'account_id' => $response['data']['id'],
            ];
        }

        return $response;
    }

    /**
     * Obtém status da conta Connect.
     */
    public function getConnectAccount(string $accountId): array
    {
        return $this->request('GET', "/accounts/{$accountId}");
    }

    /**
     * Cria transferência para conta Connect (repasse ao cuidador).
     *
     * @param array $params [
     *     'amount' => float (valor em reais),
     *     'destination' => string (account_id do cuidador),
     *     'metadata' => array,
     * ]
     */
    public function createTransfer(array $params): array
    {
        $amountInCents = (int) round($params['amount'] * 100);

        $data = [
            'amount' => $amountInCents,
            'currency' => $this->currency,
            'destination' => $params['destination'],
            'metadata' => $params['metadata'] ?? [],
        ];

        $response = $this->request('POST', '/transfers', $data);

        if ($response['success']) {
            return [
                'success' => true,
                'transfer_id' => $response['data']['id'],
                'amount' => $response['data']['amount'] / 100,
            ];
        }

        return $response;
    }

    /**
     * Cria sessão de checkout (para fluxo completo via Stripe).
     */
    public function createCheckoutSession(object $invoice, object $payment): string
    {
        $response = $this->request('POST', '/checkout/sessions', [
            'mode' => 'payment',
            'payment_intent_data' => [
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                ],
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => $this->currency,
                    'product_data' => [
                        'name' => "Fatura #{$invoice->id}",
                        'description' => "Serviços de cuidador - Carinho com Você",
                    ],
                    'unit_amount' => (int) round($payment->amount * 100),
                ],
                'quantity' => 1,
            ]],
            'success_url' => config('app.url') . '/pagamento/sucesso?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url') . '/pagamento/cancelado',
        ]);

        return $response['data']['url'] ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Valida assinatura do webhook.
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('integrations.stripe.webhook_secret');

        if (!$secret) {
            Log::warning('Stripe webhook secret não configurado');
            return false;
        }

        try {
            $elements = explode(',', $signature);
            $timestamp = null;
            $signatures = [];

            foreach ($elements as $element) {
                $parts = explode('=', $element, 2);
                if (count($parts) === 2) {
                    if ($parts[0] === 't') {
                        $timestamp = $parts[1];
                    } elseif ($parts[0] === 'v1') {
                        $signatures[] = $parts[1];
                    }
                }
            }

            if (!$timestamp || empty($signatures)) {
                return false;
            }

            // Verifica se não expirou (tolerância de 5 minutos)
            if (abs(time() - (int) $timestamp) > 300) {
                return false;
            }

            $signedPayload = $timestamp . '.' . $payload;
            $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

            foreach ($signatures as $sig) {
                if (hash_equals($expectedSignature, $sig)) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Erro ao validar webhook Stripe', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos Auxiliares
    |--------------------------------------------------------------------------
    */

    /**
     * Realiza requisição para a API Stripe.
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        if (empty($this->secretKey)) {
            Log::error('Stripe secret key não configurada');
            return [
                'success' => false,
                'error' => 'Stripe não configurado',
            ];
        }

        try {
            $request = Http::withBasicAuth($this->secretKey, '')
                ->timeout($this->timeout)
                ->asForm();

            // Adiciona headers extras
            foreach ($headers as $key => $value) {
                if ($value) {
                    $request->withHeaders([$key => $value]);
                }
            }

            $url = $this->baseUrl . $endpoint;

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $this->flattenArray($data)),
                'DELETE' => $request->delete($url),
                default => throw new \Exception("Método HTTP não suportado: {$method}"),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $error = $response->json();
            Log::warning('Stripe API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Erro desconhecido',
                'code' => $error['error']['code'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Stripe request exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mapeia tipos de método de pagamento.
     */
    protected function getPaymentMethodTypes(string $type): array
    {
        return match ($type) {
            'pix' => ['pix'],
            'boleto' => ['boleto'],
            'card' => ['card'],
            default => ['pix', 'card', 'boleto'],
        };
    }

    /**
     * Mapeia motivo de reembolso.
     */
    protected function mapRefundReason(string $reason): string
    {
        // Stripe aceita: duplicate, fraudulent, requested_by_customer
        return match (true) {
            str_contains(strtolower($reason), 'duplica') => 'duplicate',
            str_contains(strtolower($reason), 'fraud') => 'fraudulent',
            default => 'requested_by_customer',
        };
    }

    /**
     * Achata array para formato form-data do Stripe.
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
