<?php

namespace App\Integrations\Cuidadores;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integração com o sistema de Cuidadores (carinho-cuidadores).
 *
 * Responsável por:
 * - Obter dados bancários de cuidadores
 * - Obter avaliação e tempo de casa
 * - Sincronizar dados para repasses
 */
class CuidadoresClient
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('integrations.cuidadores.base_url', ''), '/');
        $this->token = config('integrations.cuidadores.token', '');
        $this->timeout = config('integrations.cuidadores.timeout', 8);
    }

    /**
     * Obtém dados de um cuidador.
     */
    public function getCaregiver(int $caregiverId): ?array
    {
        $response = $this->request('GET', "/caregivers/{$caregiverId}");

        if ($response['success']) {
            return $response['data'];
        }

        return null;
    }

    /**
     * Obtém dados bancários de um cuidador.
     */
    public function getCaregiverBankAccount(int $caregiverId): ?array
    {
        $response = $this->request('GET', "/caregivers/{$caregiverId}/bank-account");

        if ($response['success']) {
            return $response['data'];
        }

        return null;
    }

    /**
     * Obtém telefone do cuidador.
     */
    public function getCaregiverPhone(int $caregiverId): ?string
    {
        $caregiver = $this->getCaregiver($caregiverId);
        return $caregiver['phone'] ?? null;
    }

    /**
     * Obtém avaliação média do cuidador.
     */
    public function getCaregiverRating(int $caregiverId): float
    {
        $response = $this->request('GET', "/caregivers/{$caregiverId}/rating");

        if ($response['success']) {
            return (float) ($response['data']['average'] ?? 0);
        }

        return 0;
    }

    /**
     * Obtém tempo de casa em meses.
     */
    public function getCaregiverTenure(int $caregiverId): int
    {
        $caregiver = $this->getCaregiver($caregiverId);
        
        if (!$caregiver || !isset($caregiver['activated_at'])) {
            return 0;
        }

        $activatedAt = \Carbon\Carbon::parse($caregiver['activated_at']);
        return $activatedAt->diffInMonths(now());
    }

    /**
     * Notifica cuidador sobre repasse processado.
     */
    public function notifyPayoutProcessed(int $caregiverId, int $payoutId, float $amount): bool
    {
        $response = $this->request('POST', '/webhooks/internal', [
            'event' => 'payout.processed',
            'payload' => [
                'caregiver_id' => $caregiverId,
                'payout_id' => $payoutId,
                'amount' => $amount,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        return $response['success'];
    }

    /**
     * Atualiza conta Stripe Connect do cuidador.
     */
    public function updateStripeAccount(int $caregiverId, string $stripeAccountId): bool
    {
        $response = $this->request('PUT', "/caregivers/{$caregiverId}/stripe-account", [
            'stripe_account_id' => $stripeAccountId,
        ]);

        return $response['success'];
    }

    /**
     * Realiza requisição para a API de Cuidadores.
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            Log::debug('Cuidadores client não configurado');
            return ['success' => false, 'error' => 'Cuidadores não configurado'];
        }

        try {
            $request = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout);

            $url = $this->baseUrl . $endpoint;

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                default => throw new \Exception("Método não suportado: {$method}"),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? $response->json(),
                ];
            }

            Log::warning('Cuidadores request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Request failed',
            ];

        } catch (\Exception $e) {
            Log::error('Cuidadores request error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
