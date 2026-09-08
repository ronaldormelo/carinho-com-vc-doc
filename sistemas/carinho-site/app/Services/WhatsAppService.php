<?php

namespace App\Services;

use App\Models\Domain\DomainServiceType;
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
        $message .= "Urgência: {$urgency}\n\n";
        $message .= "Responda rapidamente para não perder o contato!";

        // Envia para numero de atendimento
        $atendimentoPhone = config('branding.contact.whatsapp');

        return $this->sendTextMessage($atendimentoPhone, $message);
    }

    /**
     * Envia mensagem de boas-vindas para lead.
     */
    public function sendWelcomeMessage(string $phone, string $name): array
    {
        $message = "Olá, {$name}! 👋\n\n";
        $message .= "Obrigado por entrar em contato com a Carinho com Você!\n\n";
        $message .= "Recebemos seu cadastro e um de nossos atendentes entrará em contato em breve.\n\n";
        $message .= "Se precisar de atendimento urgente, responda esta mensagem.\n\n";
        $message .= "Carinho com Você - Cuidado que faz diferença.";

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
     * Resolve a mensagem pre-preenchida a partir de uma chave conhecida.
     *
     * Chaves desconhecidas ou invalidas caem na mensagem padrao, para
     * impedir que a URL do site injete texto arbitrario no WhatsApp.
     */
    public function resolveCtaMessage(?string $key): string
    {
        $messages = config('branding.whatsapp_messages', []);
        $default = (string) ($messages['default'] ?? 'Olá! Vim pelo site e gostaria de saber mais sobre os serviços.');

        if (!is_string($key) || $key === '') {
            return $default;
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            return $default;
        }

        return (string) ($messages[$key] ?? $default);
    }

    /**
     * Escolhe a chave de mensagem de acordo com a pagina atual.
     */
    public function messageKeyForRoute(?string $routeName): string
    {
        return match ($routeName) {
            'clients' => 'quote',
            'caregivers' => 'caregiver',
            'contact' => 'contact',
            'faq' => 'faq',
            'how-it-works' => 'how_it_works',
            'services' => 'quote',
            'investors' => 'investor',
            'about' => 'about',
            default => 'default',
        };
    }

    /**
     * Mensagem do WhatsApp apos envio do formulario de cliente.
     */
    public function resolveClientLeadMessage(int|string|null $serviceTypeId): string
    {
        if ($serviceTypeId === null || $serviceTypeId === '') {
            return $this->resolveCtaMessage('client');
        }

        $key = match ((int) $serviceTypeId) {
            DomainServiceType::HORISTA => 'quote_horista',
            DomainServiceType::DIARIO => 'quote_diario',
            DomainServiceType::MENSAL => 'quote_mensal',
            default => 'client',
        };

        return $this->resolveCtaMessage($key);
    }

    /**
     * Monta o redirect wa.me com a mensagem da acao e, se houver, a origem UTM.
     */
    public function buildCtaRedirectUrl(?string $messageKey, array $utm = []): string
    {
        $message = $this->resolveCtaMessage($messageKey);

        if (
            ($utm['utm_source'] ?? '') !== ''
            || ($utm['utm_medium'] ?? '') !== ''
            || ($utm['utm_campaign'] ?? '') !== ''
        ) {
            $source = $utm['utm_source'] ?? '';
            $medium = $utm['utm_medium'] ?? '';
            $campaign = $utm['utm_campaign'] ?? '';
            $message .= "\n\n[Origem: {$source} / {$medium} / {$campaign}]";
        }

        return $this->generateCtaUrl($message);
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
