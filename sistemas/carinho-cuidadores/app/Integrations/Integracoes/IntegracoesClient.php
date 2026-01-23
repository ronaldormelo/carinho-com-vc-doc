<?php

namespace App\Integrations\Integracoes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o hub central de Integracoes.
 *
 * Este sistema centraliza eventos e automacoes entre todos os sistemas.
 *
 * Endpoints principais:
 * - POST /events - Publica evento
 * - POST /webhooks - Registra webhook
 */
class IntegracoesClient
{
    /**
     * Publica evento no hub de integracoes.
     */
    public function publishEvent(string $eventType, array $data): array
    {
        return $this->request('events', [
            'source' => 'carinho-cuidadores',
            'event_type' => $eventType,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Eventos pre-definidos.
     */
    public function caregiverCreated(int $caregiverId, array $data): array
    {
        return $this->publishEvent('caregiver.created', [
            'caregiver_id' => $caregiverId,
            ...$data,
        ]);
    }

    public function caregiverActivated(int $caregiverId, array $data): array
    {
        return $this->publishEvent('caregiver.activated', [
            'caregiver_id' => $caregiverId,
            ...$data,
        ]);
    }

    public function caregiverDeactivated(int $caregiverId, ?string $reason = null): array
    {
        return $this->publishEvent('caregiver.deactivated', [
            'caregiver_id' => $caregiverId,
            'reason' => $reason,
        ]);
    }

    public function documentUploaded(int $caregiverId, int $documentId, string $docType): array
    {
        return $this->publishEvent('document.uploaded', [
            'caregiver_id' => $caregiverId,
            'document_id' => $documentId,
            'doc_type' => $docType,
        ]);
    }

    public function documentVerified(int $caregiverId, int $documentId, string $docType): array
    {
        return $this->publishEvent('document.verified', [
            'caregiver_id' => $caregiverId,
            'document_id' => $documentId,
            'doc_type' => $docType,
        ]);
    }

    public function contractSigned(int $caregiverId, int $contractId): array
    {
        return $this->publishEvent('contract.signed', [
            'caregiver_id' => $caregiverId,
            'contract_id' => $contractId,
        ]);
    }

    public function ratingReceived(int $caregiverId, int $serviceId, int $score): array
    {
        return $this->publishEvent('rating.received', [
            'caregiver_id' => $caregiverId,
            'service_id' => $serviceId,
            'score' => $score,
        ]);
    }

    public function incidentRegistered(int $caregiverId, int $serviceId, string $incidentType): array
    {
        return $this->publishEvent('incident.registered', [
            'caregiver_id' => $caregiverId,
            'service_id' => $serviceId,
            'incident_type' => $incidentType,
        ]);
    }

    /**
     * Registra webhook para receber eventos.
     */
    public function registerWebhook(string $url, array $events): array
    {
        return $this->request('webhooks', [
            'source' => 'carinho-cuidadores',
            'url' => $url,
            'events' => $events,
        ]);
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.integracoes.timeout', 8));

            $response = match ($method) {
                'GET' => $request->get($this->endpoint($path)),
                'PATCH' => $request->patch($this->endpoint($path), $payload),
                'PUT' => $request->put($this->endpoint($path), $payload),
                'DELETE' => $request->delete($this->endpoint($path)),
                default => $request->post($this->endpoint($path), $payload),
            };

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('Integracoes request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Integracoes request error', [
                'path' => $path,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 0,
                'ok' => false,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Monta URL do endpoint.
     */
    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.integracoes.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.integracoes.token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Source' => 'carinho-cuidadores',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
