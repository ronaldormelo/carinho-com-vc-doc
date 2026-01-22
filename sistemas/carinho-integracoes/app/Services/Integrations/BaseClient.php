<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\PendingRequest;

/**
 * Cliente base para integracoes com sistemas internos Carinho.
 *
 * Fornece metodos comuns para autenticacao, retry e logging.
 */
abstract class BaseClient
{
    protected string $configKey;
    protected int $defaultTimeout = 10;
    protected int $cacheTtl = 300; // 5 minutos

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
     * Prepara request HTTP com headers de autenticacao.
     */
    protected function httpClient(): PendingRequest
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->getApiKey(),
            'X-Source-System' => 'integracoes',
        ])
            ->timeout($this->getTimeout())
            ->connectTimeout(3);
    }

    /**
     * Realiza requisicao GET.
     */
    protected function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    /**
     * Realiza requisicao POST.
     */
    protected function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, $data);
    }

    /**
     * Realiza requisicao PUT.
     */
    protected function put(string $path, array $data = []): array
    {
        return $this->request('PUT', $path, $data);
    }

    /**
     * Realiza requisicao DELETE.
     */
    protected function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    /**
     * Realiza requisicao HTTP.
     */
    protected function request(string $method, string $path, array $data = []): array
    {
        $url = $this->getBaseUrl() . '/' . ltrim($path, '/');

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

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning("{$this->configKey} API request failed", [
                    'method' => $method,
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error("{$this->configKey} API request error", [
                'method' => $method,
                'url' => $url,
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
}
