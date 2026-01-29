<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

/**
 * Classe base para integração com sistemas internos Carinho
 */
abstract class BaseInternalService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected string $serviceName;

    public function __construct(string $baseUrl, ?string $apiKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey ?? '';
        $this->timeout = 10;
        $this->serviceName = 'internal';
    }

    /**
     * Verifica se a integração está habilitada
     */
    abstract public function isEnabled(): bool;

    /**
     * Faz requisição HTTP para o serviço interno
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = [])
    {
        if (!$this->isEnabled()) {
            Log::channel('integrations')->warning("{$this->serviceName} não está habilitado");
            return null;
        }

        // Para endpoints de conteúdo, não usar /api/v1
        if (str_starts_with($endpoint, 'content/') || str_starts_with($endpoint, 'webhooks/')) {
            $url = "{$this->baseUrl}/api/{$endpoint}";
        } else {
            $url = "{$this->baseUrl}/api/v1/{$endpoint}";
        }

        $defaultHeaders = [
            'X-API-Key' => $this->apiKey,
            'X-Service-Origin' => 'carinho-crm',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(array_merge($defaultHeaders, $headers))
                ->$method($url, $data);

            if ($response->successful()) {
                Log::channel('integrations')->info("{$this->serviceName} {$method} {$endpoint} OK", [
                    'status' => $response->status(),
                ]);
                return $response->json();
            }

            Log::channel('integrations')->error("{$this->serviceName} {$method} {$endpoint} erro", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (RequestException $e) {
            Log::channel('integrations')->error("{$this->serviceName} exceção {$endpoint}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * GET request
     */
    protected function get(string $endpoint, array $query = [])
    {
        return $this->request('get', $endpoint, $query);
    }

    /**
     * POST request
     */
    protected function post(string $endpoint, array $data = [])
    {
        return $this->request('post', $endpoint, $data);
    }

    /**
     * PUT request
     */
    protected function put(string $endpoint, array $data = [])
    {
        return $this->request('put', $endpoint, $data);
    }

    /**
     * DELETE request
     */
    protected function delete(string $endpoint)
    {
        return $this->request('delete', $endpoint);
    }
}
