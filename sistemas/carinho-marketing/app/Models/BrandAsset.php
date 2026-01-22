<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ativo da marca (logos, templates, icones).
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $file_url
 * @property string|null $description
 * @property array|null $metadata
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class BrandAsset extends Model
{
    protected $table = 'brand_assets';

    protected $fillable = [
        'name',
        'type',
        'file_url',
        'description',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Tipos de ativos da marca.
     */
    public const TYPE_LOGO = 'logo';
    public const TYPE_ICON = 'icon';
    public const TYPE_TEMPLATE = 'template';
    public const TYPE_TYPOGRAPHY = 'typography';
    public const TYPE_COLOR = 'color';
    public const TYPE_PATTERN = 'pattern';

    /**
     * Verifica se e um logo.
     */
    public function isLogo(): bool
    {
        return $this->type === self::TYPE_LOGO;
    }

    /**
     * Verifica se e um template.
     */
    public function isTemplate(): bool
    {
        return $this->type === self::TYPE_TEMPLATE;
    }

    /**
     * Retorna metadado especifico.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Define metadado.
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
    }

    /**
     * Scope para ativos ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope por tipo.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para logos.
     */
    public function scopeLogos($query)
    {
        return $query->where('type', self::TYPE_LOGO);
    }

    /**
     * Scope para templates.
     */
    public function scopeTemplates($query)
    {
        return $query->where('type', self::TYPE_TEMPLATE);
    }

    /**
     * Retorna todos os tipos disponiveis.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_LOGO => 'Logo',
            self::TYPE_ICON => 'Icone',
            self::TYPE_TEMPLATE => 'Template',
            self::TYPE_TYPOGRAPHY => 'Tipografia',
            self::TYPE_COLOR => 'Cor',
            self::TYPE_PATTERN => 'Padrao',
        ];
    }

    /**
     * Retorna o logo principal.
     */
    public static function getPrimaryLogo(): ?self
    {
        return self::active()
            ->logos()
            ->where('name', 'like', '%primary%')
            ->orWhere('name', 'like', '%principal%')
            ->first();
    }
}
