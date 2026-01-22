<?php

namespace App\Services;

use App\Models\BrandAsset;
use Illuminate\Support\Facades\Cache;

/**
 * Servico de gestao da biblioteca de marca.
 */
class BrandLibraryService
{
    /**
     * Lista todos os assets da marca.
     */
    public function list(array $filters = []): array
    {
        $query = BrandAsset::query();

        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        return $query->orderBy('type')->orderBy('name')->get()->toArray();
    }

    /**
     * Obtem asset por ID.
     */
    public function get(int $id): BrandAsset
    {
        return BrandAsset::findOrFail($id);
    }

    /**
     * Cria novo asset.
     */
    public function create(array $data): BrandAsset
    {
        $this->invalidateCache();

        return BrandAsset::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'file_url' => $data['file_url'],
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Atualiza asset.
     */
    public function update(int $id, array $data): BrandAsset
    {
        $asset = BrandAsset::findOrFail($id);

        $asset->update(array_filter([
            'name' => $data['name'] ?? null,
            'file_url' => $data['file_url'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null));

        $this->invalidateCache();

        return $asset->fresh();
    }

    /**
     * Desativa asset.
     */
    public function deactivate(int $id): BrandAsset
    {
        $asset = BrandAsset::findOrFail($id);
        $asset->update(['is_active' => false]);

        $this->invalidateCache();

        return $asset;
    }

    /**
     * Ativa asset.
     */
    public function activate(int $id): BrandAsset
    {
        $asset = BrandAsset::findOrFail($id);
        $asset->update(['is_active' => true]);

        $this->invalidateCache();

        return $asset;
    }

    /**
     * Obtem logos ativos.
     */
    public function getLogos(): array
    {
        return Cache::remember('brand_logos', 3600, function () {
            return BrandAsset::active()->logos()->get()->toArray();
        });
    }

    /**
     * Obtem logo principal.
     */
    public function getPrimaryLogo(): ?BrandAsset
    {
        return Cache::remember('brand_primary_logo', 3600, function () {
            return BrandAsset::getPrimaryLogo();
        });
    }

    /**
     * Obtem templates ativos.
     */
    public function getTemplates(): array
    {
        return Cache::remember('brand_templates', 3600, function () {
            return BrandAsset::active()->templates()->get()->toArray();
        });
    }

    /**
     * Obtem configuracoes de branding.
     */
    public function getBrandingConfig(): array
    {
        return Cache::remember('branding_config', 3600, function () {
            return [
                'name' => config('branding.name'),
                'domain' => config('branding.domain'),
                'purpose' => config('branding.purpose'),
                'promise' => config('branding.promise'),
                'personality' => config('branding.personality'),
                'voice' => config('branding.voice'),
                'messages' => config('branding.messages'),
                'colors' => config('branding.colors'),
                'typography' => config('branding.typography'),
                'social' => config('branding.social'),
                'content' => config('branding.content'),
            ];
        });
    }

    /**
     * Obtem paleta de cores.
     */
    public function getColorPalette(): array
    {
        return config('branding.colors', []);
    }

    /**
     * Obtem configuracoes de tipografia.
     */
    public function getTypography(): array
    {
        return config('branding.typography', []);
    }

    /**
     * Obtem tom de voz.
     */
    public function getVoiceGuidelines(): array
    {
        return config('branding.voice', []);
    }

    /**
     * Obtem mensagens-chave.
     */
    public function getKeyMessages(): array
    {
        return config('branding.messages', []);
    }

    /**
     * Obtem hashtags da marca.
     */
    public function getHashtags(): array
    {
        return config('branding.social.hashtags', []);
    }

    /**
     * Obtem bio padrao para redes sociais.
     */
    public function getSocialBio(): string
    {
        return config('branding.social.bio_template', '');
    }

    /**
     * Obtem temas de conteudo.
     */
    public function getContentThemes(): array
    {
        return config('branding.content.themes', []);
    }

    /**
     * Obtem frequencia de postagem recomendada.
     */
    public function getPostFrequency(): string
    {
        return config('branding.content.post_frequency', '2 posts/semana');
    }

    /**
     * Gera CSS de branding.
     */
    public function generateBrandCss(): string
    {
        $colors = $this->getColorPalette();
        $typography = $this->getTypography();

        $css = ":root {\n";

        foreach ($colors as $name => $value) {
            $css .= "  --brand-{$name}: {$value};\n";
        }

        $css .= "  --font-family: {$typography['font_family']};\n";
        $css .= "  --font-family-headings: {$typography['font_family_headings']};\n";
        $css .= "  --font-size-base: {$typography['font_size_base']};\n";
        $css .= "  --line-height: {$typography['line_height']};\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Invalida cache de branding.
     */
    private function invalidateCache(): void
    {
        Cache::forget('brand_logos');
        Cache::forget('brand_primary_logo');
        Cache::forget('brand_templates');
        Cache::forget('branding_config');
    }
}
