<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditLog;
use App\Models\Domain\DomainDealStatus;

class Deal extends Model
{
    use HasFactory, HasAuditLog;

    protected $table = 'deals';

    protected $fillable = [
        'lead_id',
        'stage_id',
        'value_estimated',
        'status_id',
    ];

    protected $casts = [
        'value_estimated' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Campos auditados
    protected array $audited = ['stage_id', 'value_estimated', 'status_id'];
    protected string $logName = 'deals';

    // Relacionamentos
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function status()
    {
        return $this->belongsTo(DomainDealStatus::class, 'status_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status_id', DomainDealStatus::OPEN);
    }

    public function scopeWon($query)
    {
        return $query->where('status_id', DomainDealStatus::WON);
    }

    public function scopeLost($query)
    {
        return $query->where('status_id', DomainDealStatus::LOST);
    }

    public function scopeInStage($query, int $stageId)
    {
        return $query->where('stage_id', $stageId);
    }

    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeWithMinValue($query, float $minValue)
    {
        return $query->where('value_estimated', '>=', $minValue);
    }

    // Métodos de negócio
    public function isOpen(): bool
    {
        return $this->status_id === DomainDealStatus::OPEN;
    }

    public function isWon(): bool
    {
        return $this->status_id === DomainDealStatus::WON;
    }

    public function isLost(): bool
    {
        return $this->status_id === DomainDealStatus::LOST;
    }

    public function canMoveToStage(int $stageId): bool
    {
        return $this->isOpen() && PipelineStage::where('id', $stageId)->where('active', true)->exists();
    }

    public function moveToNextStage(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        $nextStage = $this->stage->getNextStage();
        if (!$nextStage) {
            return false;
        }

        $this->stage_id = $nextStage->id;
        return $this->save();
    }

    public function getLatestProposal(): ?Proposal
    {
        return $this->proposals()->latest()->first();
    }

    public function getDaysInCurrentStage(): int
    {
        return $this->updated_at->diffInDays(now());
    }

    public function getTotalDaysInPipeline(): int
    {
        return $this->created_at->diffInDays(now());
    }
}
