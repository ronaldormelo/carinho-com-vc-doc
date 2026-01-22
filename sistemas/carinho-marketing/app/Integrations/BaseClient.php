<?php

namespace App\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Cliente base para integrações externas.
 */
abstract class BaseClient
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $connectTimeout;
    protected string $cachePrefix;

    /**
     * Realiza requisicao GET.
     */
    protected function get(string $endpoint, array $params = [], array $headers = []): array
    {
        return $this->request('GET', $endpoint, $params, $headers);
    }

    /**
     * Realiza requisicao POST.
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $endpoint, $data, $headers);
    }

    /**
     * Realiza requisicao PUT.
     */
    protected function put(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('PUT', $endpoint, $data, $headers);
    }

    /**
     * Realiza requisicao DELETE.
     */
    protected function delete(string $endpoint, array $headers = []): array
    {
        return $this->request('DELETE', $endpoint, [], $headers);
    }

    /**
     * Realiza a requisicao HTTP.
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $url = $this->buildUrl($endpoint);
            $allHeaders = array_merge($this->getDefaultHeaders(), $headers);

            $request = Http::withHeaders($allHeaders)
                ->connectTimeout($this->connectTimeout ?? 5)
                ->timeout($this->timeout ?? 30);

            $response = match ($method) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url),
                default => throw new \InvalidArgumentException("Metodo HTTP invalido: {$method}"),
            };

            $result = [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning(static::class . ' request failed', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error(static::class . ' request error', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 0,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Constroi URL completa.
     */
    protected function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Retorna headers padrao.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Armazena em cache.
     */
    protected function cache(string $key, callable $callback, int $ttl = 3600)
    {
        $cacheKey = ($this->cachePrefix ?? 'api') . ':' . $key;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Invalida cache.
     */
    protected function forgetCache(string $key): void
    {
        $cacheKey = ($this->cachePrefix ?? 'api') . ':' . $key;
        Cache::forget($cacheKey);
    }
}
