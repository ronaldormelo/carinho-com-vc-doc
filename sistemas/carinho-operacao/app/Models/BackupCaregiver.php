<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Banco de cuidadores backup por região.
 * 
 * Mantém lista de cuidadores disponíveis para substituição rápida,
 * organizados por região e prioridade, conforme práticas de contingência.
 *
 * @property int $id
 * @property int $caregiver_id
 * @property string $region_code
 * @property int $priority
 * @property bool $is_available
 * @property ?string $available_from
 * @property ?string $available_until
 * @property ?array $service_types
 * @property ?string $last_assignment_at
 * @property string $created_at
 * @property string $updated_at
 */
class BackupCaregiver extends Model
{
    protected $table = 'backup_caregivers';

    protected $fillable = [
        'caregiver_id',
        'region_code',
        'priority',
        'is_available',
        'available_from',
        'available_until',
        'service_types',
        'last_assignment_at',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'service_types' => 'array',
        'last_assignment_at' => 'datetime',
    ];

    // Prioridades
    const PRIORITY_HIGH = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_LOW = 3;

    /**
     * Verifica se está disponível agora.
     */
    public function isAvailableNow(): bool
    {
        if (!$this->is_available) {
            return false;
        }

        $now = Carbon::now()->format('H:i:s');

        if ($this->available_from && $now < $this->available_from) {
            return false;
        }

        if ($this->available_until && $now > $this->available_until) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se atende um tipo de serviço.
     */
    public function supportsServiceType(int $serviceTypeId): bool
    {
        if (empty($this->service_types)) {
            return true; // Sem restrição = aceita todos
        }

        return in_array($serviceTypeId, $this->service_types);
    }

    /**
     * Registra que foi utilizado.
     */
    public function markAsUsed(): self
    {
        $this->last_assignment_at = now();
        $this->save();

        return $this;
    }

    /**
     * Scope por região.
     */
    public function scopeInRegion($query, string $regionCode)
    {
        return $query->where('region_code', $regionCode);
    }

    /**
     * Scope para disponíveis.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope ordenado por prioridade e uso.
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc')
            ->orderBy('last_assignment_at', 'asc'); // Menos usado primeiro
    }

    /**
     * Scope por tipo de serviço.
     */
    public function scopeForServiceType($query, int $serviceTypeId)
    {
        return $query->where(function ($q) use ($serviceTypeId) {
            $q->whereNull('service_types')
              ->orWhereJsonContains('service_types', $serviceTypeId);
        });
    }

    /**
     * Labels de prioridade.
     */
    public static function priorityLabels(): array
    {
        return [
            self::PRIORITY_HIGH => 'Alta',
            self::PRIORITY_MEDIUM => 'Média',
            self::PRIORITY_LOW => 'Baixa',
        ];
    }
}
