<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasCacheableQueries;

class PipelineStage extends Model
{
    use HasFactory, HasCacheableQueries;

    protected $table = 'pipeline_stages';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'stage_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // Relacionamentos
    public function deals()
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('stage_order', 'asc');
    }

    // Métodos de negócio
    public function getNextStage(): ?self
    {
        return self::where('stage_order', '>', $this->stage_order)
            ->where('active', true)
            ->orderBy('stage_order', 'asc')
            ->first();
    }

    public function getPreviousStage(): ?self
    {
        return self::where('stage_order', '<', $this->stage_order)
            ->where('active', true)
            ->orderBy('stage_order', 'desc')
            ->first();
    }

    public function isFirstStage(): bool
    {
        return !self::where('stage_order', '<', $this->stage_order)
            ->where('active', true)
            ->exists();
    }

    public function isLastStage(): bool
    {
        return !self::where('stage_order', '>', $this->stage_order)
            ->where('active', true)
            ->exists();
    }

    public function getDealsCount(): int
    {
        return $this->deals()
            ->where('status_id', \App\Models\Domain\DomainDealStatus::OPEN)
            ->count();
    }

    public function getDealsValue(): float
    {
        return $this->deals()
            ->where('status_id', \App\Models\Domain\DomainDealStatus::OPEN)
            ->sum('value_estimated');
    }
}
