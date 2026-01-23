<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

/**
 * Tipos de evento para histórico padronizado
 * 
 * Categoriza eventos para facilitar filtros e relatórios no histórico do cliente.
 */
class DomainEventType extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_event_type';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label', 'category'];

    // Constantes para categorias
    public const CATEGORY_COMMERCIAL = 'commercial';
    public const CATEGORY_OPERATIONAL = 'operational';
    public const CATEGORY_FINANCIAL = 'financial';
    public const CATEGORY_COMMUNICATION = 'communication';

    // Constantes para tipos de evento - Comercial
    public const LEAD_CREATED = 1;
    public const LEAD_QUALIFIED = 2;
    public const PROPOSAL_SENT = 3;
    public const PROPOSAL_ACCEPTED = 4;
    public const PROPOSAL_REJECTED = 5;
    public const DEAL_WON = 6;
    public const DEAL_LOST = 7;

    // Constantes para tipos de evento - Operacional
    public const CLIENT_CREATED = 10;
    public const CONTRACT_CREATED = 11;
    public const CONTRACT_SIGNED = 12;
    public const CONTRACT_ACTIVATED = 13;
    public const CONTRACT_RENEWED = 14;
    public const CONTRACT_CLOSED = 15;
    public const REVIEW_SCHEDULED = 16;
    public const REVIEW_COMPLETED = 17;

    // Constantes para tipos de evento - Financeiro
    public const PAYMENT_RECEIVED = 20;
    public const PAYMENT_OVERDUE = 21;
    public const INVOICE_SENT = 22;

    // Constantes para tipos de evento - Comunicação
    public const CONTACT_WHATSAPP = 30;
    public const CONTACT_PHONE = 31;
    public const CONTACT_EMAIL = 32;
    public const COMPLAINT = 33;
    public const FEEDBACK_POSITIVE = 34;
    public const FEEDBACK_NEGATIVE = 35;
    public const REFERRAL_MADE = 36;

    /**
     * Relacionamento com eventos
     */
    public function events()
    {
        return $this->hasMany(\App\Models\ClientEvent::class, 'event_type_id');
    }

    /**
     * Obter tipos de evento por categoria
     */
    public static function getByCategory(string $category)
    {
        return static::cacheAll()->where('category', $category);
    }

    /**
     * Obter todas as categorias disponíveis
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_COMMERCIAL => 'Comercial',
            self::CATEGORY_OPERATIONAL => 'Operacional',
            self::CATEGORY_FINANCIAL => 'Financeiro',
            self::CATEGORY_COMMUNICATION => 'Comunicação',
        ];
    }
}
