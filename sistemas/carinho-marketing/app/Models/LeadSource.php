<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de origem do lead.
 *
 * @property int $id
 * @property string $lead_id
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_content
 * @property string|null $utm_term
 * @property int|null $campaign_id
 * @property int|null $landing_page_id
 * @property string|null $referrer
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $extra_data
 * @property \Carbon\Carbon $captured_at
 * @property \Carbon\Carbon $created_at
 */
class LeadSource extends Model
{
    public $timestamps = false;

    protected $table = 'lead_sources';

    protected $fillable = [
        'lead_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'campaign_id',
        'landing_page_id',
        'referrer',
        'ip_address',
        'user_agent',
        'extra_data',
        'captured_at',
        'created_at',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'landing_page_id' => 'integer',
        'extra_data' => 'array',
        'captured_at' => 'datetime',
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
            $model->captured_at = $model->captured_at ?? now();
        });
    }

    /**
     * Relacionamento com campanha.
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Relacionamento com landing page.
     */
    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class, 'landing_page_id');
    }

    /**
     * Verifica se veio de campanha paga.
     */
    public function isPaid(): bool
    {
        $paidMediums = ['cpc', 'ppc', 'paid', 'paidsocial', 'cpm'];

        return in_array(strtolower($this->utm_medium ?? ''), $paidMediums);
    }

    /**
     * Verifica se veio de redes sociais.
     */
    public function isSocial(): bool
    {
        $socialSources = ['facebook', 'instagram', 'linkedin', 'twitter', 'tiktok'];

        return in_array(strtolower($this->utm_source ?? ''), $socialSources);
    }

    /**
     * Verifica se veio de busca organica.
     */
    public function isOrganic(): bool
    {
        return strtolower($this->utm_medium ?? '') === 'organic';
    }

    /**
     * Verifica se e referral.
     */
    public function isReferral(): bool
    {
        return strtolower($this->utm_medium ?? '') === 'referral';
    }

    /**
     * Retorna o canal de origem formatado.
     */
    public function getChannelAttribute(): string
    {
        if ($this->isPaid()) {
            return 'Midia Paga';
        }

        if ($this->isSocial()) {
            return 'Redes Sociais';
        }

        if ($this->isOrganic()) {
            return 'Busca Organica';
        }

        if ($this->isReferral()) {
            return 'Indicacao';
        }

        return 'Direto';
    }

    /**
     * Cria registro a partir de parametros UTM da URL.
     */
    public static function createFromRequest(string $leadId, array $utmParams, array $extraData = []): self
    {
        return self::create([
            'lead_id' => $leadId,
            'utm_source' => $utmParams['utm_source'] ?? null,
            'utm_medium' => $utmParams['utm_medium'] ?? null,
            'utm_campaign' => $utmParams['utm_campaign'] ?? null,
            'utm_content' => $utmParams['utm_content'] ?? null,
            'utm_term' => $utmParams['utm_term'] ?? null,
            'referrer' => $extraData['referrer'] ?? null,
            'ip_address' => $extraData['ip'] ?? null,
            'user_agent' => $extraData['user_agent'] ?? null,
            'extra_data' => $extraData['extra'] ?? null,
        ]);
    }

    /**
     * Scope por source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('utm_source', strtolower($source));
    }

    /**
     * Scope por medium.
     */
    public function scopeByMedium($query, string $medium)
    {
        return $query->where('utm_medium', strtolower($medium));
    }

    /**
     * Scope por campanha.
     */
    public function scopeByCampaign($query, string $campaign)
    {
        return $query->where('utm_campaign', strtolower($campaign));
    }

    /**
     * Scope por periodo.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('captured_at', [$startDate, $endDate]);
    }

    /**
     * Agrupa leads por source.
     */
    public static function countBySource(?string $startDate = null, ?string $endDate = null): array
    {
        $query = self::selectRaw('utm_source, COUNT(*) as total')
            ->groupBy('utm_source');

        if ($startDate && $endDate) {
            $query->whereBetween('captured_at', [$startDate, $endDate]);
        }

        return $query->pluck('total', 'utm_source')->toArray();
    }
}
