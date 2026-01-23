<?php

namespace App\Services;

use App\Models\ConversionEvent;
use App\Models\LeadSource;
use App\Integrations\Meta\ConversionApiClient;
use App\Integrations\Google\GoogleAdsClient;
use App\Integrations\Google\GoogleAnalyticsClient;
use App\Integrations\Internal\CrmClient;
use App\Integrations\Internal\IntegracoesClient;
use Illuminate\Support\Facades\Log;

/**
 * Servico de gestao de conversoes.
 */
class ConversionService
{
    public function __construct(
        private ConversionApiClient $metaCapi,
        private GoogleAdsClient $googleAds,
        private GoogleAnalyticsClient $googleAnalytics,
        private CrmClient $crm,
        private IntegracoesClient $integracoes
    ) {}

    /**
     * Registra conversao de lead.
     */
    public function registerLeadConversion(array $leadData, array $sourceData = []): array
    {
        $results = [
            'lead_source' => null,
            'facebook' => null,
            'google' => null,
            'analytics' => null,
            'crm' => null,
        ];

        try {
            // Registra origem do lead
            $leadSource = LeadSource::createFromRequest(
                $leadData['id'] ?? uniqid('lead_'),
                $sourceData,
                [
                    'referrer' => $leadData['referrer'] ?? null,
                    'ip' => $leadData['ip'] ?? null,
                    'user_agent' => $leadData['user_agent'] ?? null,
                ]
            );
            $results['lead_source'] = $leadSource->toArray();

            // Envia para Facebook Conversions API
            $results['facebook'] = $this->sendToFacebook('Lead', $leadData);

            // Envia para Google (se tiver GCLID)
            if (!empty($leadData['gclid'])) {
                $results['google'] = $this->sendToGoogle($leadData);
            }

            // Envia para Google Analytics
            $results['analytics'] = $this->sendToAnalytics('generate_lead', $leadData);

            // Envia para CRM
            $results['crm'] = $this->crm->sendLead([
                'name' => $leadData['name'] ?? null,
                'email' => $leadData['email'] ?? null,
                'phone' => $leadData['phone'] ?? null,
                'source' => $sourceData,
            ]);

            // Dispara evento no hub
            $this->integracoes->dispatchLeadCreated($leadData, $sourceData);

            Log::info('Lead conversion registered', [
                'lead_id' => $leadData['id'] ?? 'unknown',
                'source' => $sourceData['utm_source'] ?? 'direct',
            ]);

        } catch (\Throwable $e) {
            Log::error('Lead conversion registration failed', [
                'error' => $e->getMessage(),
                'lead' => $leadData,
            ]);

            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Registra conversao de contato (WhatsApp).
     */
    public function registerContactConversion(array $contactData, array $sourceData = []): array
    {
        $results = [];

        try {
            // Envia para Facebook
            $results['facebook'] = $this->sendToFacebook('Contact', $contactData);

            // Envia para Google Analytics
            $results['analytics'] = $this->sendToAnalytics('contact', array_merge($contactData, [
                'method' => 'whatsapp',
            ]));

            // Dispara evento
            $this->integracoes->dispatchConversion('contact', $contactData);

            Log::info('Contact conversion registered', [
                'phone' => $contactData['phone'] ?? 'unknown',
            ]);

        } catch (\Throwable $e) {
            Log::error('Contact conversion registration failed', [
                'error' => $e->getMessage(),
            ]);

            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Registra conversao de cadastro completo.
     */
    public function registerRegistrationConversion(array $userData, array $sourceData = []): array
    {
        $results = [];

        try {
            // Envia para Facebook
            $results['facebook'] = $this->sendToFacebook('CompleteRegistration', $userData);

            // Envia para Google
            if (!empty($userData['gclid'])) {
                $results['google'] = $this->sendToGoogle($userData, 'registration');
            }

            // Envia para Google Analytics
            $results['analytics'] = $this->sendToAnalytics('sign_up', $userData);

            // Atualiza lead no CRM
            if (!empty($userData['lead_id'])) {
                $results['crm'] = $this->crm->registerConversion(
                    $userData['lead_id'],
                    'registration',
                    $userData
                );
            }

            Log::info('Registration conversion registered');

        } catch (\Throwable $e) {
            Log::error('Registration conversion failed', [
                'error' => $e->getMessage(),
            ]);

            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Envia evento para Facebook Conversions API.
     */
    private function sendToFacebook(string $eventName, array $data): ?array
    {
        try {
            $userData = [
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'first_name' => $data['first_name'] ?? $data['name'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'client_ip_address' => $data['ip'] ?? null,
                'client_user_agent' => $data['user_agent'] ?? null,
                'fbc' => $data['fbc'] ?? null,
                'fbp' => $data['fbp'] ?? null,
            ];

            return match ($eventName) {
                'Lead' => $this->metaCapi->sendLeadEvent($userData, $data['url'] ?? null),
                'Contact' => $this->metaCapi->sendContactEvent($userData, $data['url'] ?? null),
                'CompleteRegistration' => $this->metaCapi->sendCompleteRegistrationEvent($userData, $data['url'] ?? null),
                default => $this->metaCapi->sendEvent([
                    'event_name' => $eventName,
                    'user_data' => $userData,
                ]),
            };

        } catch (\Throwable $e) {
            Log::warning('Facebook conversion send failed', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Envia conversao para Google Ads.
     */
    private function sendToGoogle(array $data, string $type = 'lead'): ?array
    {
        try {
            $conversionActionId = config("integrations.google_ads.conversion_actions.{$type}");

            if (!$conversionActionId) {
                return null;
            }

            return $this->googleAds->uploadEnhancedConversion(
                $conversionActionId,
                now()->format('Y-m-d H:i:sP'),
                [
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'first_name' => $data['first_name'] ?? $data['name'] ?? null,
                ],
                $data['value'] ?? null
            );

        } catch (\Throwable $e) {
            Log::warning('Google conversion send failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Envia evento para Google Analytics.
     */
    private function sendToAnalytics(string $eventName, array $data): ?array
    {
        try {
            $clientId = $data['ga_client_id'] ?? GoogleAnalyticsClient::generateClientId();

            return $this->googleAnalytics->sendEvent(
                $clientId,
                $eventName,
                array_filter([
                    'currency' => 'BRL',
                    'value' => $data['value'] ?? null,
                    'method' => $data['method'] ?? null,
                    'source' => $data['utm_source'] ?? null,
                    'campaign' => $data['utm_campaign'] ?? null,
                ]),
                $data['user_id'] ?? null
            );

        } catch (\Throwable $e) {
            Log::warning('Analytics event send failed', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Lista eventos de conversao configurados.
     */
    public function listEvents(): array
    {
        return ConversionEvent::all()->toArray();
    }

    /**
     * Cria evento de conversao.
     */
    public function createEvent(array $data): ConversionEvent
    {
        return ConversionEvent::create([
            'name' => $data['name'],
            'event_key' => $data['event_key'],
            'target_url' => $data['target_url'],
        ]);
    }

    /**
     * Obtem estatisticas de conversao por origem.
     */
    public function getConversionStats(string $startDate, string $endDate): array
    {
        return [
            'by_source' => LeadSource::countBySource($startDate, $endDate),
            'total' => LeadSource::inPeriod($startDate, $endDate)->count(),
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];
    }
}
