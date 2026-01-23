<?php

namespace App\Jobs;

use App\Integrations\Meta\ConversionApiClient;
use App\Integrations\Google\GoogleAdsClient;
use App\Integrations\Google\GoogleAnalyticsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar eventos de conversao para plataformas.
 */
class SendConversionEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 3;

    /**
     * Tempo limite em segundos.
     */
    public int $timeout = 60;

    /**
     * Tempo de backoff entre tentativas.
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private string $eventType,
        private array $userData,
        private array $platforms = ['facebook', 'google', 'analytics']
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ConversionApiClient $metaCapi,
        GoogleAdsClient $googleAds,
        GoogleAnalyticsClient $analytics
    ): void {
        try {
            $results = [];

            // Envia para Facebook
            if (in_array('facebook', $this->platforms)) {
                try {
                    $results['facebook'] = match ($this->eventType) {
                        'lead' => $metaCapi->sendLeadEvent($this->userData),
                        'contact' => $metaCapi->sendContactEvent($this->userData),
                        'registration' => $metaCapi->sendCompleteRegistrationEvent($this->userData),
                        default => $metaCapi->sendEvent([
                            'event_name' => $this->eventType,
                            'user_data' => $this->userData,
                        ]),
                    };
                } catch (\Throwable $e) {
                    Log::warning('Facebook conversion failed', ['error' => $e->getMessage()]);
                }
            }

            // Envia para Google
            if (in_array('google', $this->platforms) && !empty($this->userData['gclid'])) {
                try {
                    $conversionActionId = config("integrations.google_ads.conversion_actions.{$this->eventType}");
                    if ($conversionActionId) {
                        $results['google'] = $googleAds->uploadEnhancedConversion(
                            $conversionActionId,
                            now()->format('Y-m-d H:i:sP'),
                            $this->userData
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning('Google conversion failed', ['error' => $e->getMessage()]);
                }
            }

            // Envia para Analytics
            if (in_array('analytics', $this->platforms)) {
                try {
                    $clientId = $this->userData['ga_client_id'] ?? GoogleAnalyticsClient::generateClientId();
                    $eventName = match ($this->eventType) {
                        'lead' => 'generate_lead',
                        'contact' => 'contact',
                        'registration' => 'sign_up',
                        default => $this->eventType,
                    };

                    $results['analytics'] = $analytics->sendEvent($clientId, $eventName, $this->userData);
                } catch (\Throwable $e) {
                    Log::warning('Analytics event failed', ['error' => $e->getMessage()]);
                }
            }

            Log::info('Conversion event sent', [
                'event_type' => $this->eventType,
                'results' => $results,
            ]);

        } catch (\Throwable $e) {
            Log::error('SendConversionEvent failed', [
                'event_type' => $this->eventType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendConversionEvent job failed permanently', [
            'event_type' => $this->eventType,
            'error' => $exception->getMessage(),
        ]);
    }
}
