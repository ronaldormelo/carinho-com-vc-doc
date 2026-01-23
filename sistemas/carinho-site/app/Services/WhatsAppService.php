<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servico de integracao com WhatsApp via Z-API.
 *
 * Documentacao: https://developer.z-api.io/
 */
class WhatsAppService
{
    /**
     * Envia mensagem de texto.
     */
    public function sendTextMessage(string $phone, string $message): array
    {
        return $this->request('send-text', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
    }

    /**
     * Envia notificacao de novo lead.
     */
    public function sendNewLeadNotification(string $phone, string $name, string $urgency): array
    {
        $message = "Novo lead recebido!\n\n";
        $message .= "Nome: {$name}\n";
        $message .= "Telefone: {$phone}\n";
        $message .= "Urgencia: {$urgency}\n\n";
        $message .= "Responda rapidamente para nao perder o contato!";

        // Envia para numero de atendimento
        $atendimentoPhone = config('branding.contact.whatsapp');

        return $this->sendTextMessage($atendimentoPhone, $message);
    }

    /**
     * Envia mensagem de boas-vindas para lead.
     */
    public function sendWelcomeMessage(string $phone, string $name): array
    {
        $message = "Ola, {$name}! 👋\n\n";
        $message .= "Obrigado por entrar em contato com a Carinho com Voce!\n\n";
        $message .= "Recebemos seu cadastro e um de nossos atendentes entrara em contato em breve.\n\n";
        $message .= "Se precisar de atendimento urgente, responda esta mensagem.\n\n";
        $message .= "Carinho com Voce - Cuidado que faz diferenca.";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Gera URL do WhatsApp para CTA.
     */
    public function generateCtaUrl(string $message = '', array $utm = []): string
    {
        $phone = config('branding.contact.whatsapp');
        $url = "https://wa.me/{$phone}";

        if ($message) {
            $url .= "?text=" . urlencode($message);
        }

        // Adiciona UTM se configurado para tracking
        if (!empty($utm)) {
            $utmString = http_build_query($utm);
            $url .= ($message ? '&' : '?') . $utmString;
        }

        return $url;
    }

    /**
     * Verifica status da instancia.
     */
    public function getInstanceStatus(): array
    {
        return $this->request('status', [], 'GET');
    }

    /**
     * Verifica se instancia esta conectada.
     */
    public function isConnected(): bool
    {
        if (!config('integrations.whatsapp.enabled')) {
            return false;
        }

        $status = $this->getInstanceStatus();
        return $status['ok'] && ($status['data']['connected'] ?? false);
    }

    /**
     * Realiza requisicao para a Z-API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        if (!config('integrations.whatsapp.enabled')) {
            Log::info('Z-API disabled, skipping request', ['path' => $path]);
            return [
                'ok' => false,
                'error' => 'Z-API integration is disabled',
            ];
        }

        try {
            $request = Http::withHeaders($this->headers())
                ->connectTimeout((int) config('integrations.whatsapp.connect_timeout', 3))
                ->timeout((int) config('integrations.whatsapp.timeout', 10));

            if ($method === 'GET') {
                $response = $request->get($this->endpoint($path));
            } else {
                $response = $request->post($this->endpoint($path), $payload);
            }

            $result = [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('Z-API request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Z-API request error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Monta URL do endpoint.
     */
    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.whatsapp.base_url'), '/');
        $instanceId = config('integrations.whatsapp.instance_id');
        $token = config('integrations.whatsapp.token');

        return "{$baseUrl}/instances/{$instanceId}/token/{$token}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $clientToken = config('integrations.whatsapp.client_token');
        if ($clientToken) {
            $headers['client-token'] = $clientToken;
        }

        return $headers;
    }

    /**
     * Normaliza numero de telefone.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        // Adiciona codigo do Brasil se necessario
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            if (!str_starts_with($digits, '55')) {
                $digits = '55' . $digits;
            }
        }

        return $digits;
    }
}
