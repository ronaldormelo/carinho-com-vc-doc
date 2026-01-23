<?php

namespace App\Integrations\Meta;

use App\Integrations\BaseClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cliente para Facebook Conversions API.
 *
 * Documentacao: https://developers.facebook.com/docs/marketing-api/conversions-api/
 *
 * Endpoint principal:
 * - POST /{pixel-id}/events - Envia eventos de conversao
 */
class ConversionApiClient extends BaseClient
{
    private string $accessToken;
    private string $pixelId;
    private string $apiVersion;

    public function __construct()
    {
        $this->baseUrl = config('integrations.meta.base_url', 'https://graph.facebook.com');
        $this->apiVersion = config('integrations.meta.api_version', 'v18.0');
        $this->accessToken = config('integrations.meta.access_token', '');
        $this->pixelId = config('integrations.meta.pixel_id', '');
        $this->timeout = (int) config('integrations.meta.timeout', 30);
        $this->connectTimeout = (int) config('integrations.meta.connect_timeout', 5);
        $this->cachePrefix = 'meta_capi';
    }

    /**
     * Envia evento de conversao.
     */
    public function sendEvent(array $eventData): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'data' => [
                $this->formatEvent($eventData),
            ],
        ];

        if (config('app.debug')) {
            $payload['test_event_code'] = config('integrations.meta.test_event_code');
        }

        return $this->post("{$this->pixelId}/events", $payload);
    }

    /**
     * Envia multiplos eventos de conversao.
     */
    public function sendEvents(array $events): array
    {
        $formattedEvents = array_map(
            fn ($event) => $this->formatEvent($event),
            $events
        );

        $payload = [
            'access_token' => $this->accessToken,
            'data' => $formattedEvents,
        ];

        if (config('app.debug')) {
            $payload['test_event_code'] = config('integrations.meta.test_event_code');
        }

        return $this->post("{$this->pixelId}/events", $payload);
    }

    /**
     * Envia evento de Lead.
     */
    public function sendLeadEvent(array $userData, ?string $eventSourceUrl = null): array
    {
        return $this->sendEvent([
            'event_name' => 'Lead',
            'action_source' => 'website',
            'event_source_url' => $eventSourceUrl ?? config('integrations.site.base_url'),
            'user_data' => $userData,
        ]);
    }

    /**
     * Envia evento de Contact.
     */
    public function sendContactEvent(array $userData, ?string $eventSourceUrl = null): array
    {
        return $this->sendEvent([
            'event_name' => 'Contact',
            'action_source' => 'website',
            'event_source_url' => $eventSourceUrl ?? config('integrations.site.base_url'),
            'user_data' => $userData,
        ]);
    }

    /**
     * Envia evento de Complete Registration.
     */
    public function sendCompleteRegistrationEvent(array $userData, ?string $eventSourceUrl = null): array
    {
        return $this->sendEvent([
            'event_name' => 'CompleteRegistration',
            'action_source' => 'website',
            'event_source_url' => $eventSourceUrl ?? config('integrations.site.base_url'),
            'user_data' => $userData,
        ]);
    }

    /**
     * Envia evento de Page View.
     */
    public function sendPageViewEvent(array $userData, string $pageUrl): array
    {
        return $this->sendEvent([
            'event_name' => 'PageView',
            'action_source' => 'website',
            'event_source_url' => $pageUrl,
            'user_data' => $userData,
        ]);
    }

    /**
     * Envia evento de View Content.
     */
    public function sendViewContentEvent(array $userData, string $contentName, ?string $contentCategory = null): array
    {
        return $this->sendEvent([
            'event_name' => 'ViewContent',
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => [
                'content_name' => $contentName,
                'content_category' => $contentCategory,
            ],
        ]);
    }

    /**
     * Formata evento para envio.
     */
    private function formatEvent(array $eventData): array
    {
        $event = [
            'event_name' => $eventData['event_name'],
            'event_time' => $eventData['event_time'] ?? time(),
            'action_source' => $eventData['action_source'] ?? 'website',
            'event_id' => $eventData['event_id'] ?? Str::uuid()->toString(),
        ];

        if (isset($eventData['event_source_url'])) {
            $event['event_source_url'] = $eventData['event_source_url'];
        }

        // Formata user_data com hash
        if (isset($eventData['user_data'])) {
            $event['user_data'] = $this->formatUserData($eventData['user_data']);
        }

        // Custom data
        if (isset($eventData['custom_data'])) {
            $event['custom_data'] = $eventData['custom_data'];
        }

        return $event;
    }

    /**
     * Formata e faz hash dos dados do usuario.
     */
    private function formatUserData(array $userData): array
    {
        $formatted = [];

        // Email (hash SHA256)
        if (isset($userData['email'])) {
            $formatted['em'] = hash('sha256', strtolower(trim($userData['email'])));
        }

        // Telefone (hash SHA256, apenas digitos)
        if (isset($userData['phone'])) {
            $phone = preg_replace('/\D/', '', $userData['phone']);
            // Adiciona codigo do pais se nao tiver
            if (strlen($phone) <= 11 && !str_starts_with($phone, '55')) {
                $phone = '55' . $phone;
            }
            $formatted['ph'] = hash('sha256', $phone);
        }

        // Nome (hash SHA256)
        if (isset($userData['first_name'])) {
            $formatted['fn'] = hash('sha256', strtolower(trim($userData['first_name'])));
        }

        // Sobrenome (hash SHA256)
        if (isset($userData['last_name'])) {
            $formatted['ln'] = hash('sha256', strtolower(trim($userData['last_name'])));
        }

        // Cidade (hash SHA256)
        if (isset($userData['city'])) {
            $formatted['ct'] = hash('sha256', strtolower(trim($userData['city'])));
        }

        // Estado (hash SHA256)
        if (isset($userData['state'])) {
            $formatted['st'] = hash('sha256', strtolower(trim($userData['state'])));
        }

        // CEP (hash SHA256)
        if (isset($userData['zip'])) {
            $formatted['zp'] = hash('sha256', preg_replace('/\D/', '', $userData['zip']));
        }

        // Pais
        $formatted['country'] = hash('sha256', strtolower($userData['country'] ?? 'br'));

        // IP do cliente (nao precisa hash)
        if (isset($userData['client_ip_address'])) {
            $formatted['client_ip_address'] = $userData['client_ip_address'];
        }

        // User agent (nao precisa hash)
        if (isset($userData['client_user_agent'])) {
            $formatted['client_user_agent'] = $userData['client_user_agent'];
        }

        // FBC - Facebook click ID
        if (isset($userData['fbc'])) {
            $formatted['fbc'] = $userData['fbc'];
        }

        // FBP - Facebook browser ID
        if (isset($userData['fbp'])) {
            $formatted['fbp'] = $userData['fbp'];
        }

        // External ID
        if (isset($userData['external_id'])) {
            $formatted['external_id'] = hash('sha256', $userData['external_id']);
        }

        return $formatted;
    }

    /**
     * Constroi URL com versao da API.
     */
    protected function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . $this->apiVersion . '/' . ltrim($endpoint, '/');
    }

    /**
     * Eventos padrao disponiveis.
     */
    public static function getStandardEvents(): array
    {
        return [
            'PageView' => 'Visualizacao de pagina',
            'ViewContent' => 'Visualizacao de conteudo',
            'Lead' => 'Lead capturado',
            'Contact' => 'Contato realizado',
            'CompleteRegistration' => 'Cadastro completo',
            'InitiateCheckout' => 'Inicio de checkout',
            'Purchase' => 'Compra',
            'Schedule' => 'Agendamento',
        ];
    }
}
