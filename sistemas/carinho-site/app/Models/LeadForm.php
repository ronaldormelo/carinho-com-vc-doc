<?php

namespace App\Models;

use App\Models\Domain\DomainFormTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formulario de captacao de leads.
 *
 * @property int $id
 * @property string $name
 * @property int $target_type_id
 * @property array $fields_json
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class LeadForm extends Model
{
    protected $table = 'lead_forms';

    protected $fillable = [
        'name',
        'target_type_id',
        'fields_json',
        'active',
    ];

    protected $casts = [
        'fields_json' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Relacao com tipo de publico.
     */
    public function targetType(): BelongsTo
    {
        return $this->belongsTo(DomainFormTarget::class, 'target_type_id');
    }

    /**
     * Relacao com submissoes.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    /**
     * Scope para formularios ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
