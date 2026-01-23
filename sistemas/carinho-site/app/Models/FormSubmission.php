<?php

namespace App\Models;

use App\Models\Domain\DomainServiceType;
use App\Models\Domain\DomainUrgencyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Submissao de formulario de lead.
 *
 * @property int $id
 * @property int $form_id
 * @property int|null $utm_id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string $city
 * @property int $urgency_id
 * @property int $service_type_id
 * @property \Carbon\Carbon|null $consent_at
 * @property array $payload_json
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool $synced_to_crm
 * @property \Carbon\Carbon $created_at
 */
class FormSubmission extends Model
{
    protected $table = 'form_submissions';
    public $timestamps = false;

    protected $fillable = [
        'form_id',
        'utm_id',
        'name',
        'phone',
        'email',
        'city',
        'urgency_id',
        'service_type_id',
        'consent_at',
        'payload_json',
        'ip_address',
        'user_agent',
        'synced_to_crm',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'consent_at' => 'datetime',
        'synced_to_crm' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Boot do model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    /**
     * Relacao com formulario.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(LeadForm::class, 'form_id');
    }

    /**
     * Relacao com UTM.
     */
    public function utm(): BelongsTo
    {
        return $this->belongsTo(UtmCampaign::class, 'utm_id');
    }

    /**
     * Relacao com urgencia.
     */
    public function urgency(): BelongsTo
    {
        return $this->belongsTo(DomainUrgencyLevel::class, 'urgency_id');
    }

    /**
     * Relacao com tipo de servico.
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(DomainServiceType::class, 'service_type_id');
    }

    /**
     * Scope para submissoes nao sincronizadas.
     */
    public function scopeNotSynced($query)
    {
        return $query->where('synced_to_crm', false);
    }

    /**
     * Normaliza numero de telefone.
     */
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = preg_replace('/\D+/', '', $value ?? '');
    }

    /**
     * Marca como sincronizado com CRM.
     */
    public function markAsSynced(): void
    {
        $this->update(['synced_to_crm' => true]);
    }
}
