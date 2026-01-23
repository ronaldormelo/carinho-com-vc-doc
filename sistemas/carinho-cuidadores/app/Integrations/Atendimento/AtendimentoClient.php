<?php

namespace App\Integrations\Atendimento;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para integracao com o sistema de Atendimento.
 *
 * Endpoints principais:
 * - POST /notifications - Registra notificacao enviada
 * - POST /messages - Envia mensagem via canal de atendimento
 * - GET /conversations - Consulta conversas
 */
class AtendimentoClient
{
    /**
     * Registra notificacao enviada.
     */
    public function logNotification(array $payload): array
    {
        return $this->request('notifications', [
            'source' => 'carinho-cuidadores',
            'caregiver_id' => $payload['caregiver_id'] ?? null,
            'type' => $payload['type'] ?? null,
            'channels' => $payload['channels'] ?? [],
            'timestamp' => $payload['timestamp'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Envia mensagem via sistema de atendimento.
     */
    public function sendMessage(string $phone, string $message, array $options = []): array
    {
        return $this->request('messages', [
            'phone' => $phone,
            'message' => $message,
            'source' => 'carinho-cuidadores',
            'options' => $options,
        ]);
    }

    /**
     * Consulta conversas de um cuidador.
     */
    public function getConversations(string $phone): array
    {
        $normalizedPhone = preg_replace('/\D+/', '', $phone);

        return $this->request("conversations?phone={$normalizedPhone}&source=cuidadores", [], 'GET');
    }

    /**
     * Cria conversa no sistema de atendimento.
     */
    public function createConversation(array $payload): array
    {
        return $this->request('conversations', [
            'phone' => $payload['phone'] ?? null,
            'name' => $payload['name'] ?? null,
            'source' => 'carinho-cuidadores',
            'type' => 'caregiver',
            'caregiver_id' => $payload['caregiver_id'] ?? null,
        ]);
    }

    /**
     * Envia comunicado para cuidadores.
     */
    public function sendBroadcast(array $phones, string $message, array $options = []): array
    {
        return $this->request('broadcasts', [
            'phones' => $phones,
            'message' => $message,
            'source' => 'carinho-cuidadores',
            'options' => $options,
        ]);
    }

    /**
     * Solicita avaliacao de servico.
     */
    public function requestServiceRating(array $payload): array
    {
        return $this->request('rating-requests', [
            'service_id' => $payload['service_id'] ?? null,
            'caregiver_id' => $payload['caregiver_id'] ?? null,
            'client_phone' => $payload['client_phone'] ?? null,
            'source' => 'carinho-cuidadores',
        ]);
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.atendimento.timeout', 8));

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
                Log::warning('Atendimento request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Atendimento request error', [
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
        $baseUrl = rtrim((string) config('integrations.atendimento.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $token = config('integrations.atendimento.token');

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
