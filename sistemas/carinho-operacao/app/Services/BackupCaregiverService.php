<?php

namespace App\Services;

use App\Models\BackupCaregiver;
use App\Models\Assignment;
use App\Integrations\Cuidadores\CuidadoresClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de banco de cuidadores backup.
 * 
 * Mantém lista organizada de cuidadores disponíveis para substituição
 * rápida, conforme práticas de contingência operacional.
 */
class BackupCaregiverService
{
    public function __construct(
        protected CuidadoresClient $cuidadoresClient
    ) {}

    /**
     * Adiciona cuidador ao banco de backup.
     */
    public function addToBackup(
        int $caregiverId,
        string $regionCode,
        int $priority = BackupCaregiver::PRIORITY_MEDIUM,
        ?string $availableFrom = null,
        ?string $availableUntil = null,
        ?array $serviceTypes = null
    ): BackupCaregiver {
        $backup = BackupCaregiver::updateOrCreate(
            [
                'caregiver_id' => $caregiverId,
                'region_code' => $regionCode,
            ],
            [
                'priority' => $priority,
                'is_available' => true,
                'available_from' => $availableFrom,
                'available_until' => $availableUntil,
                'service_types' => $serviceTypes,
            ]
        );

        Log::info('Cuidador adicionado ao banco de backup', [
            'caregiver_id' => $caregiverId,
            'region_code' => $regionCode,
            'priority' => $priority,
        ]);

        return $backup;
    }

    /**
     * Remove cuidador do banco de backup.
     */
    public function removeFromBackup(int $caregiverId, ?string $regionCode = null): int
    {
        $query = BackupCaregiver::where('caregiver_id', $caregiverId);

        if ($regionCode) {
            $query->where('region_code', $regionCode);
        }

        $deleted = $query->delete();

        Log::info('Cuidador removido do banco de backup', [
            'caregiver_id' => $caregiverId,
            'region_code' => $regionCode,
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Marca cuidador como disponível/indisponível.
     */
    public function setAvailability(int $caregiverId, bool $isAvailable, ?string $regionCode = null): int
    {
        $query = BackupCaregiver::where('caregiver_id', $caregiverId);

        if ($regionCode) {
            $query->where('region_code', $regionCode);
        }

        return $query->update(['is_available' => $isAvailable]);
    }

    /**
     * Busca cuidadores backup disponíveis para uma região.
     */
    public function findAvailableBackups(
        string $regionCode,
        ?int $serviceTypeId = null,
        int $limit = 5
    ): Collection {
        $query = BackupCaregiver::inRegion($regionCode)
            ->available()
            ->orderedByPriority();

        if ($serviceTypeId) {
            $query->forServiceType($serviceTypeId);
        }

        $backups = $query->limit($limit)->get();

        // Filtra por disponibilidade de horário
        $now = now();
        return $backups->filter(fn($backup) => $backup->isAvailableNow());
    }

    /**
     * Busca o melhor cuidador backup para substituição imediata.
     */
    public function findBestBackup(
        string $regionCode,
        ?int $serviceTypeId = null
    ): ?BackupCaregiver {
        return $this->findAvailableBackups($regionCode, $serviceTypeId, 1)->first();
    }

    /**
     * Busca backups em regiões próximas quando não há na região principal.
     */
    public function findBackupWithExpansion(
        string $regionCode,
        ?int $serviceTypeId = null,
        array $nearbyRegions = []
    ): ?BackupCaregiver {
        // Primeiro tenta na região principal
        $backup = $this->findBestBackup($regionCode, $serviceTypeId);

        if ($backup) {
            return $backup;
        }

        // Expande para regiões próximas
        foreach ($nearbyRegions as $nearbyRegion) {
            $backup = $this->findBestBackup($nearbyRegion, $serviceTypeId);
            if ($backup) {
                Log::info('Backup encontrado em região alternativa', [
                    'original_region' => $regionCode,
                    'backup_region' => $nearbyRegion,
                    'caregiver_id' => $backup->caregiver_id,
                ]);
                return $backup;
            }
        }

        return null;
    }

    /**
     * Utiliza um cuidador backup e registra o uso.
     */
    public function useBackup(BackupCaregiver $backup): BackupCaregiver
    {
        return $backup->markAsUsed();
    }

    /**
     * Atualiza prioridade do cuidador backup.
     */
    public function updatePriority(int $caregiverId, string $regionCode, int $priority): ?BackupCaregiver
    {
        $backup = BackupCaregiver::where('caregiver_id', $caregiverId)
            ->where('region_code', $regionCode)
            ->first();

        if ($backup) {
            $backup->priority = $priority;
            $backup->save();
        }

        return $backup;
    }

    /**
     * Atualiza horários de disponibilidade.
     */
    public function updateAvailabilityWindow(
        int $caregiverId,
        string $regionCode,
        ?string $availableFrom,
        ?string $availableUntil
    ): ?BackupCaregiver {
        $backup = BackupCaregiver::where('caregiver_id', $caregiverId)
            ->where('region_code', $regionCode)
            ->first();

        if ($backup) {
            $backup->available_from = $availableFrom;
            $backup->available_until = $availableUntil;
            $backup->save();
        }

        return $backup;
    }

    /**
     * Obtém estatísticas do banco de backup.
     */
    public function getBackupStats(): array
    {
        $total = BackupCaregiver::count();
        $available = BackupCaregiver::available()->count();

        $byRegion = BackupCaregiver::available()
            ->select('region_code')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('region_code')
            ->pluck('count', 'region_code')
            ->toArray();

        $byPriority = BackupCaregiver::available()
            ->select('priority')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        // Regiões com poucos backups (alerta)
        $lowCoverageRegions = collect($byRegion)
            ->filter(fn($count) => $count < 3)
            ->keys()
            ->toArray();

        return [
            'total' => $total,
            'available' => $available,
            'by_region' => $byRegion,
            'by_priority' => $byPriority,
            'low_coverage_regions' => $lowCoverageRegions,
            'coverage_rate' => $total > 0 ? round(($available / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Obtém histórico de uso do banco de backup.
     */
    public function getUsageHistory(?string $regionCode = null, int $days = 30): Collection
    {
        $query = BackupCaregiver::whereNotNull('last_assignment_at')
            ->where('last_assignment_at', '>=', now()->subDays($days));

        if ($regionCode) {
            $query->inRegion($regionCode);
        }

        return $query->orderBy('last_assignment_at', 'desc')->get();
    }

    /**
     * Sincroniza banco de backup com sistema de cuidadores.
     */
    public function syncWithCuidadores(): array
    {
        $result = [
            'checked' => 0,
            'updated' => 0,
            'deactivated' => 0,
        ];

        $backups = BackupCaregiver::all();

        foreach ($backups as $backup) {
            $result['checked']++;

            // Verifica status no sistema de cuidadores
            $response = $this->cuidadoresClient->getCaregiver($backup->caregiver_id);

            if (!$response['ok']) {
                // Cuidador não encontrado ou inativo
                $backup->is_available = false;
                $backup->save();
                $result['deactivated']++;
                continue;
            }

            $caregiverData = $response['body']['caregiver'] ?? [];
            
            // Verifica se ainda está ativo
            if (($caregiverData['status'] ?? null) !== 'active') {
                $backup->is_available = false;
                $backup->save();
                $result['deactivated']++;
                continue;
            }

            $result['updated']++;
        }

        Log::info('Sincronização de banco de backup concluída', $result);

        return $result;
    }

    /**
     * Obtém cuidadores backup por região.
     */
    public function getBackupsByRegion(string $regionCode): Collection
    {
        return BackupCaregiver::inRegion($regionCode)
            ->orderedByPriority()
            ->get();
    }

    /**
     * Lista todas as regiões com backup configurado.
     */
    public function getConfiguredRegions(): Collection
    {
        return BackupCaregiver::select('region_code')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available')
            ->groupBy('region_code')
            ->orderBy('region_code')
            ->get();
    }
}
