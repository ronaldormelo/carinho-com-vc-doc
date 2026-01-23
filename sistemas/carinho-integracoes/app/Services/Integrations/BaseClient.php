<?php

namespace App\Services\Integrations;

use App\Services\CircuitBreaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\Client\PendingRequest;

/**
 * Cliente base para integracoes com sistemas internos Carinho.
 *
 * Fornece metodos comuns para autenticacao, retry, logging e circuit breaker.
 *
 * Práticas consolidadas:
 * - Circuit breaker para fail-fast
 * - Logging estruturado com request_id
 * - Métricas de latência
 * - Cache inteligente
 */
abstract class BaseClient
{
    protected string $configKey;
    protected int $defaultTimeout = 10;
    protected int $cacheTtl = 300; // 5 minutos
    protected ?CircuitBreaker $circuitBreaker = null;

    /**
     * Retorna URL base do sistema.
     */
    protected function getBaseUrl(): string
    {
        return rtrim(config("integrations.{$this->configKey}.url"), '/');
    }

    /**
     * Retorna API key do sistema.
     */
    protected function getApiKey(): ?string
    {
        return config("integrations.{$this->configKey}.api_key");
    }

    /**
     * Retorna timeout configurado.
     */
    protected function getTimeout(): int
    {
        return (int) config("integrations.{$this->configKey}.timeout", $this->defaultTimeout);
    }

    /**
     * Obtém instância do circuit breaker.
     */
    protected function getCircuitBreaker(): CircuitBreaker
    {
        if ($this->circuitBreaker === null) {
            $this->circuitBreaker = app(CircuitBreaker::class);
        }

        return $this->circuitBreaker;
    }

    /**
     * Prepara request HTTP com headers de autenticacao.
     */
    protected function httpClient(): PendingRequest
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->getApiKey(),
            'X-Source-System' => 'integracoes',
            'X-Request-ID' => $this->generateRequestId(),
        ])
            ->timeout($this->getTimeout())
            ->connectTimeout(3);
    }

    /**
     * Gera ID único para rastreamento da requisição.
     */
    protected function generateRequestId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Realiza requisicao GET.
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    /**
     * Realiza requisicao POST.
     */
    public function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, $data);
    }

    /**
     * Realiza requisicao PUT.
     */
    public function put(string $path, array $data = []): array
    {
        return $this->request('PUT', $path, $data);
    }

    /**
     * Realiza requisicao DELETE.
     */
    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    /**
     * Realiza requisicao HTTP com circuit breaker e logging estruturado.
     */
    protected function request(string $method, string $path, array $data = []): array
    {
        $url = $this->getBaseUrl() . '/' . ltrim($path, '/');
        $requestId = $this->generateRequestId();
        $startTime = microtime(true);

        // Verifica circuit breaker
        $circuitBreaker = $this->getCircuitBreaker();
        if (!$circuitBreaker->canExecute($this->configKey)) {
            Log::warning("Circuit breaker open for {$this->configKey}", [
                'request_id' => $requestId,
                'service' => $this->configKey,
                'method' => $method,
                'path' => $path,
            ]);

            return [
                'status' => 503,
                'ok' => false,
                'body' => null,
                'error' => "Service {$this->configKey} temporarily unavailable (circuit breaker open)",
                'circuit_breaker' => true,
            ];
        }

        try {
            $client = $this->httpClient();

            $response = match (strtoupper($method)) {
                'GET' => $client->get($url, $data),
                'POST' => $client->post($url, $data),
                'PUT' => $client->put($url, $data),
                'PATCH' => $client->patch($url, $data),
                'DELETE' => $client->delete($url),
                default => throw new \InvalidArgumentException("Metodo HTTP invalido: {$method}"),
            };

            $duration = (microtime(true) - $startTime) * 1000;

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
                'duration_ms' => round($duration, 2),
            ];

            if ($response->successful()) {
                // Registra sucesso no circuit breaker
                $circuitBreaker->recordSuccess($this->configKey);

                // Log de sucesso (nivel debug para não poluir)
                Log::debug("{$this->configKey} API request success", [
                    'request_id' => $requestId,
                    'service' => $this->configKey,
                    'method' => $method,
                    'path' => $path,
                    'status' => $response->status(),
                    'duration_ms' => round($duration, 2),
                ]);
            } else {
                // Registra falha no circuit breaker (apenas para erros de servidor)
                if ($response->serverError()) {
                    $circuitBreaker->recordFailure($this->configKey);
                }

                // Log de falha
                Log::warning("{$this->configKey} API request failed", [
                    'request_id' => $requestId,
                    'service' => $this->configKey,
                    'method' => $method,
                    'path' => $path,
                    'status' => $response->status(),
                    'duration_ms' => round($duration, 2),
                    'response' => $this->sanitizeResponseForLog($response->body()),
                ]);
            }

            return $result;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Erro de conexão - registra no circuit breaker
            $circuitBreaker->recordFailure($this->configKey);

            $duration = (microtime(true) - $startTime) * 1000;

            Log::error("{$this->configKey} API connection error", [
                'request_id' => $requestId,
                'service' => $this->configKey,
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
                'error_type' => 'connection',
            ]);

            return [
                'status' => 0,
                'ok' => false,
                'body' => null,
                'error' => $e->getMessage(),
                'error_type' => 'connection',
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error("{$this->configKey} API request error", [
                'request_id' => $requestId,
                'service' => $this->configKey,
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
                'error_type' => get_class($e),
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
     * Sanitiza resposta para log (remove dados sensíveis).
     */
    protected function sanitizeResponseForLog(string $body): string
    {
        // Limita tamanho do log
        if (strlen($body) > 1000) {
            return substr($body, 0, 1000) . '... (truncated)';
        }

        // Remove campos sensíveis comuns
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $sensitiveFields = ['password', 'token', 'api_key', 'secret', 'cpf', 'rg'];
            foreach ($sensitiveFields as $field) {
                if (isset($decoded[$field])) {
                    $decoded[$field] = '[REDACTED]';
                }
            }
            return json_encode($decoded);
        }

        return $body;
    }

    /**
     * GET com cache.
     */
    protected function getCached(string $path, array $query = [], ?int $ttl = null): array
    {
        $cacheKey = $this->configKey . '_' . md5($path . json_encode($query));

        return Cache::remember($cacheKey, $ttl ?? $this->cacheTtl, function () use ($path, $query) {
            return $this->get($path, $query);
        });
    }

    /**
     * Invalida cache.
     */
    protected function invalidateCache(string $path, array $query = []): void
    {
        $cacheKey = $this->configKey . '_' . md5($path . json_encode($query));
        Cache::forget($cacheKey);
    }

    /**
     * Verifica se servico esta disponivel.
     */
    public function healthCheck(): bool
    {
        $response = $this->get('/health');

        return $response['ok'];
    }

    /**
     * Verifica estado do circuit breaker para este serviço.
     */
    public function getCircuitBreakerStatus(): array
    {
        return $this->getCircuitBreaker()->getStatus($this->configKey);
    }

    /**
     * Reseta circuit breaker manualmente.
     */
    public function resetCircuitBreaker(): void
    {
        $this->getCircuitBreaker()->reset($this->configKey);
    }
}
