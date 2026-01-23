<?php

namespace App\Services;

use App\Models\UtmLink;
use Illuminate\Support\Str;

/**
 * Servico de construcao de links UTM.
 */
class UtmBuilderService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('integrations.utm.base_url', 'https://carinho.com.vc');
    }

    /**
     * Cria link UTM.
     */
    public function create(array $data): UtmLink
    {
        return UtmLink::createFromParams(
            $data['source'],
            $data['medium'],
            $data['campaign'],
            $data['content'] ?? null,
            $data['term'] ?? null
        );
    }

    /**
     * Gera URL completa com UTM.
     */
    public function buildUrl(array $params, ?string $baseUrl = null): string
    {
        $baseUrl = $baseUrl ?? $this->baseUrl;

        $utmParams = array_filter([
            'utm_source' => $this->normalize($params['source'] ?? null),
            'utm_medium' => $this->normalize($params['medium'] ?? null),
            'utm_campaign' => $this->normalize($params['campaign'] ?? null),
            'utm_content' => $this->normalize($params['content'] ?? null),
            'utm_term' => $this->normalize($params['term'] ?? null),
        ]);

        if (empty($utmParams)) {
            return $baseUrl;
        }

        $queryString = http_build_query($utmParams);
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl . $separator . $queryString;
    }

    /**
     * Gera URL para WhatsApp com UTM.
     */
    public function buildWhatsAppUrl(array $params, ?string $phone = null, ?string $message = null): string
    {
        $utmUrl = $this->buildUrl($params);
        $whatsAppBase = 'https://wa.me';

        $text = $message ?? config('branding.social.cta_default', '');
        if ($utmUrl) {
            $text .= "\n\n" . $utmUrl;
        }

        $encodedText = urlencode(trim($text));

        if ($phone) {
            $phone = preg_replace('/\D/', '', $phone);
            if (!str_starts_with($phone, '55')) {
                $phone = '55' . $phone;
            }
            return "{$whatsAppBase}/{$phone}?text={$encodedText}";
        }

        return "{$whatsAppBase}?text={$encodedText}";
    }

    /**
     * Gera URL curta para bio de redes sociais.
     */
    public function buildBioUrl(string $platform): string
    {
        return $this->buildUrl([
            'source' => $platform,
            'medium' => 'bio',
            'campaign' => 'organic',
        ]);
    }

    /**
     * Gera UTM para campanha.
     */
    public function buildCampaignUrl(string $campaignName, string $source, string $medium, ?string $content = null): string
    {
        return $this->buildUrl([
            'source' => $source,
            'medium' => $medium,
            'campaign' => $campaignName,
            'content' => $content,
        ]);
    }

    /**
     * Gera UTM para email marketing.
     */
    public function buildEmailUrl(string $campaignName, ?string $content = null): string
    {
        return $this->buildUrl([
            'source' => 'email',
            'medium' => 'email',
            'campaign' => $campaignName,
            'content' => $content,
        ]);
    }

    /**
     * Gera UTM para anuncio do Facebook/Instagram.
     */
    public function buildMetaAdUrl(string $campaignName, ?string $adSet = null, ?string $ad = null): string
    {
        return $this->buildUrl([
            'source' => 'facebook',
            'medium' => 'cpc',
            'campaign' => $campaignName,
            'content' => $adSet ? "{$adSet}_{$ad}" : $ad,
        ]);
    }

    /**
     * Gera UTM para anuncio do Google.
     */
    public function buildGoogleAdUrl(string $campaignName, ?string $adGroup = null, ?string $keyword = null): string
    {
        return $this->buildUrl([
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => $campaignName,
            'content' => $adGroup,
            'term' => $keyword,
        ]);
    }

    /**
     * Extrai parametros UTM de uma URL.
     */
    public function parseUrl(string $url): array
    {
        $parts = parse_url($url);
        $query = $parts['query'] ?? '';
        parse_str($query, $params);

        return array_filter([
            'source' => $params['utm_source'] ?? null,
            'medium' => $params['utm_medium'] ?? null,
            'campaign' => $params['utm_campaign'] ?? null,
            'content' => $params['utm_content'] ?? null,
            'term' => $params['utm_term'] ?? null,
        ]);
    }

    /**
     * Lista links UTM criados.
     */
    public function list(array $filters = []): array
    {
        $query = UtmLink::query();

        if (!empty($filters['source'])) {
            $query->bySource($filters['source']);
        }

        if (!empty($filters['medium'])) {
            $query->byMedium($filters['medium']);
        }

        if (!empty($filters['campaign'])) {
            $query->byCampaign($filters['campaign']);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Obtem link UTM por ID.
     */
    public function get(int $id): UtmLink
    {
        return UtmLink::findOrFail($id);
    }

    /**
     * Normaliza valor para UTM.
     */
    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::slug($value, '-');
    }

    /**
     * Retorna sources comuns.
     */
    public static function getSources(): array
    {
        return [
            'google' => 'Google',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'twitter' => 'Twitter',
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'organic' => 'Organico',
            'direct' => 'Direto',
            'referral' => 'Indicacao',
        ];
    }

    /**
     * Retorna mediums comuns.
     */
    public static function getMediums(): array
    {
        return [
            'cpc' => 'CPC (Pago por clique)',
            'cpm' => 'CPM (Pago por impressao)',
            'organic' => 'Organico',
            'social' => 'Redes Sociais',
            'email' => 'Email',
            'referral' => 'Indicacao',
            'affiliate' => 'Afiliado',
            'display' => 'Display',
            'video' => 'Video',
            'bio' => 'Bio',
        ];
    }
}
