<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Consent extends Model
{
    use HasFactory;

    protected $table = 'consents';
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'consent_type',
        'granted_at',
        'source',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
    ];

    // Tipos de consentimento
    public const TYPE_DATA_PROCESSING = 'data_processing';
    public const TYPE_MARKETING = 'marketing';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_EMAIL = 'email';
    public const TYPE_SHARING = 'sharing';
    public const TYPE_TERMS_OF_SERVICE = 'terms_of_service';

    // Fontes de consentimento
    public const SOURCE_SITE_FORM = 'site_form';
    public const SOURCE_WHATSAPP = 'whatsapp';
    public const SOURCE_CONTRACT = 'contract';
    public const SOURCE_MANUAL = 'manual';

    // Relacionamentos
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeDataProcessing($query)
    {
        return $query->where('consent_type', self::TYPE_DATA_PROCESSING);
    }

    public function scopeMarketing($query)
    {
        return $query->where('consent_type', self::TYPE_MARKETING);
    }

    public function scopeGrantedAfter($query, $date)
    {
        return $query->where('granted_at', '>=', $date);
    }

    // Métodos de negócio
    public static function availableTypes(): array
    {
        return [
            self::TYPE_DATA_PROCESSING => 'Processamento de dados',
            self::TYPE_MARKETING => 'Comunicações de marketing',
            self::TYPE_WHATSAPP => 'Contato via WhatsApp',
            self::TYPE_EMAIL => 'Contato via e-mail',
            self::TYPE_SHARING => 'Compartilhamento com terceiros',
            self::TYPE_TERMS_OF_SERVICE => 'Termos de serviço',
        ];
    }

    public static function availableSources(): array
    {
        return [
            self::SOURCE_SITE_FORM => 'Formulário do site',
            self::SOURCE_WHATSAPP => 'WhatsApp',
            self::SOURCE_CONTRACT => 'Contrato',
            self::SOURCE_MANUAL => 'Registro manual',
        ];
    }

    public function getTypeLabel(): string
    {
        return self::availableTypes()[$this->consent_type] ?? $this->consent_type;
    }

    public function getSourceLabel(): string
    {
        return self::availableSources()[$this->source] ?? $this->source;
    }
}
