<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasEncryptedFields;
use App\Traits\HasAuditLog;
use App\Models\Domain\DomainUrgencyLevel;
use App\Models\Domain\DomainServiceType;
use App\Models\Domain\DomainLeadStatus;

class Lead extends Model
{
    use HasFactory, HasEncryptedFields, HasAuditLog;

    protected $table = 'leads';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'city',
        'urgency_id',
        'service_type_id',
        'source',
        'status_id',
        'utm_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Campos criptografados (LGPD)
    protected array $encrypted = ['phone', 'email'];

    // Campos auditados
    protected array $audited = ['name', 'phone', 'email', 'city', 'status_id', 'urgency_id', 'service_type_id'];
    protected string $logName = 'leads';

    /**
     * Accessor para telefone descriptografado
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->getDecryptedAttribute('phone');
    }

    /**
     * Accessor para email descriptografado
     */
    public function getEmailAttribute(): ?string
    {
        return $this->getDecryptedAttribute('email');
    }

    // Relacionamentos
    public function urgency()
    {
        return $this->belongsTo(DomainUrgencyLevel::class, 'urgency_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(DomainServiceType::class, 'service_type_id');
    }

    public function status()
    {
        return $this->belongsTo(DomainLeadStatus::class, 'status_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function lossReason()
    {
        return $this->hasOne(LossReason::class);
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status_id', DomainLeadStatus::NEW);
    }

    public function scopeTriage($query)
    {
        return $query->where('status_id', DomainLeadStatus::TRIAGE);
    }

    public function scopeProposal($query)
    {
        return $query->where('status_id', DomainLeadStatus::PROPOSAL);
    }

    public function scopeActive($query)
    {
        return $query->where('status_id', DomainLeadStatus::ACTIVE);
    }

    public function scopeLost($query)
    {
        return $query->where('status_id', DomainLeadStatus::LOST);
    }

    public function scopeInPipeline($query)
    {
        return $query->whereIn('status_id', DomainLeadStatus::activeStatuses());
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', 'LIKE', "%{$city}%");
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeUrgent($query)
    {
        return $query->where('urgency_id', DomainUrgencyLevel::HOJE);
    }

    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Métodos de negócio
    public function isInPipeline(): bool
    {
        return in_array($this->status_id, DomainLeadStatus::activeStatuses());
    }

    public function isConverted(): bool
    {
        return $this->status_id === DomainLeadStatus::ACTIVE;
    }

    public function isLost(): bool
    {
        return $this->status_id === DomainLeadStatus::LOST;
    }

    public function canAdvanceTo(int $statusId): bool
    {
        $allowedTransitions = [
            DomainLeadStatus::NEW => [DomainLeadStatus::TRIAGE, DomainLeadStatus::LOST],
            DomainLeadStatus::TRIAGE => [DomainLeadStatus::PROPOSAL, DomainLeadStatus::LOST],
            DomainLeadStatus::PROPOSAL => [DomainLeadStatus::ACTIVE, DomainLeadStatus::LOST],
        ];

        return isset($allowedTransitions[$this->status_id]) &&
               in_array($statusId, $allowedTransitions[$this->status_id]);
    }

    public function getLastInteraction(): ?Interaction
    {
        return $this->interactions()->latest('occurred_at')->first();
    }

    public function getDaysSinceLastContact(): ?int
    {
        $lastInteraction = $this->getLastInteraction();
        
        if (!$lastInteraction) {
            return null;
        }

        return $lastInteraction->occurred_at->diffInDays(now());
    }
}
