<?php

namespace App\Integrations\Financeiro;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente Financeiro. Destino real: /invoices, /payouts, /webhooks/internal.
 */
class FinanceiroClient
{
    public function registerService(array $data): array
    {
        return $this->notifyInternal('service.completed', [
            'service_id' => $data['service_request_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'hours' => $data['hours'] ?? null,
            'registered_at' => now()->toIso8601String(),
        ]);
    }

    public function completeService(int $serviceRequestId, array $data): array
    {
        return $this->notifyInternal('service.completed', [
            'service_id' => $serviceRequestId,
            'client_id' => $data['client_id'] ?? null,
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'actual_hours' => $data['actual_hours'] ?? null,
            'completed_at' => $data['completed_at'] ?? now()->toIso8601String(),
        ]);
    }

    public function registerCancellation(array $data): array
    {
        if (!empty($data['invoice_id'])) {
            return $this->request("invoices/{$data['invoice_id']}/cancel", [
                'reason' => $data['reason'] ?? 'Cancelamento operacional',
            ]);
        }

        return $this->notifyInternal('service.cancelled', [
            'service_request_id' => $data['service_request_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'fee_type' => $data['fee_type'] ?? null,
            'canceled_at' => $data['canceled_at'] ?? now()->toIso8601String(),
        ]);
    }

    public function requestRepasse(array $data): array
    {
        $periodEnd = now()->toDateString();
        $periodStart = now()->subDays(7)->toDateString();

        return $this->request('payouts', [
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'period_start' => $data['period_start'] ?? $periodStart,
            'period_end' => $data['period_end'] ?? $periodEnd,
        ]);
    }

    public function getServiceFinancialStatus(int $serviceRequestId): array
    {
        return $this->request('invoices', [
            'external_reference' => (string) $serviceRequestId,
        ], 'GET');
    }

    public function registerWorkedHours(array $data): array
    {
        return $this->notifyInternal('service.completed', [
            'service_id' => $data['schedule_id'] ?? $data['service_request_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'caregiver_id' => $data['caregiver_id'] ?? null,
            'hours' => $data['total_hours'] ?? null,
            'shift_date' => $data['shift_date'] ?? null,
        ]);
    }

    public function notifyEvent(string $eventType, array $data): array
    {
        return $this->notifyInternal($eventType, $data);
    }

    private function notifyInternal(string $event, array $payload): array
    {
        return $this->requestHost('webhooks/internal', [
            'event' => $event,
            'payload' => $payload,
        ]);
    }

    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        return $this->send($this->endpoint($path), $payload, $method, $path);
    }

    private function requestHost(string $path, array $payload = [], string $method = 'POST'): array
    {
        $baseUrl = rtrim((string) config('integrations.financeiro.base_url'), '/');
        $host = preg_replace('#/api$#', '', $baseUrl);

        return $this->send("{$host}/{$path}", $payload, $method, $path);
    }

    private function send(string $url, array $payload, string $method, string $path): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout((int) config('integrations.financeiro.timeout', 10));

            $response = match ($method) {
                'GET' => $request->get($url, $payload),
                'PATCH' => $request->patch($url, $payload),
                'PUT' => $request->put($url, $payload),
                'DELETE' => $request->delete($url),
                default => $request->post($url, $payload),
            };

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('Financeiro request failed', [
                    'path' => $path,
                    'method' => $method,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Financeiro request error', [
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

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.financeiro.base_url'), '/');

        return "{$baseUrl}/{$path}";
    }

    private function headers(): array
    {
        $token = config('integrations.financeiro.token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Source' => 'carinho-operacao',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
            $headers['X-Internal-Token'] = $token;
        }

        return $headers;
    }
}
