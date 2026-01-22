<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Evento de conversao para rastreamento.
 *
 * @property int $id
 * @property string $name
 * @property string $event_key
 * @property string $target_url
 * @property \Carbon\Carbon $created_at
 */
class ConversionEvent extends Model
{
    public $timestamps = false;

    protected $table = 'conversion_events';

    protected $fillable = [
        'name',
        'event_key',
        'target_url',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Boot do model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    /**
     * Gera o pixel do Facebook para o evento.
     */
    public function getFacebookPixelCode(): string
    {
        $pixelId = config('integrations.meta.pixel_id');

        return <<<HTML
<script>
fbq('track', '{$this->event_key}', {
    content_name: '{$this->name}',
    content_category: 'lead'
});
</script>
HTML;
    }

    /**
     * Gera o codigo do Google Tag Manager para o evento.
     */
    public function getGtmCode(): string
    {
        return <<<HTML
<script>
dataLayer.push({
    'event': '{$this->event_key}',
    'eventCategory': 'conversion',
    'eventAction': '{$this->name}'
});
</script>
HTML;
    }

    /**
     * Retorna payload para API de conversao do Facebook.
     */
    public function getFacebookConversionPayload(array $userData = []): array
    {
        return [
            'event_name' => $this->event_key,
            'event_time' => time(),
            'action_source' => 'website',
            'event_source_url' => $this->target_url,
            'user_data' => array_filter([
                'em' => $userData['email'] ?? null,
                'ph' => $userData['phone'] ?? null,
                'fn' => $userData['first_name'] ?? null,
                'ln' => $userData['last_name'] ?? null,
                'ct' => $userData['city'] ?? null,
                'st' => $userData['state'] ?? null,
                'zp' => $userData['zip'] ?? null,
                'country' => $userData['country'] ?? 'br',
            ]),
        ];
    }

    /**
     * Retorna payload para API de conversao do Google Ads.
     */
    public function getGoogleConversionPayload(array $userData = []): array
    {
        return [
            'conversionAction' => $this->event_key,
            'conversionDateTime' => now()->format('Y-m-d H:i:sP'),
            'userIdentifiers' => array_filter([
                [
                    'hashedEmail' => isset($userData['email'])
                        ? hash('sha256', strtolower(trim($userData['email'])))
                        : null,
                ],
                [
                    'hashedPhoneNumber' => isset($userData['phone'])
                        ? hash('sha256', preg_replace('/\D/', '', $userData['phone']))
                        : null,
                ],
            ]),
        ];
    }

    /**
     * Scope por event key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('event_key', $key);
    }

    /**
     * Busca por event key.
     */
    public static function findByKey(string $key): ?self
    {
        return self::where('event_key', $key)->first();
    }

    /**
     * Eventos padrao de conversao.
     */
    public static function getStandardEvents(): array
    {
        return [
            'Lead' => 'Novo lead capturado',
            'Contact' => 'Contato realizado',
            'CompleteRegistration' => 'Cadastro completo',
            'ViewContent' => 'Visualizacao de conteudo',
            'InitiateCheckout' => 'Inicio de contratacao',
            'Purchase' => 'Contratacao finalizada',
        ];
    }
}
