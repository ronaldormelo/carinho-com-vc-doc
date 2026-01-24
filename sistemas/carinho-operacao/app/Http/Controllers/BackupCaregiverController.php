<?php

namespace App\Http\Controllers;

use App\Services\BackupCaregiverService;
use App\Models\BackupCaregiver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de banco de cuidadores backup.
 */
class BackupCaregiverController extends Controller
{
    public function __construct(
        protected BackupCaregiverService $backupService
    ) {}

    /**
     * Lista cuidadores backup por região.
     */
    public function index(Request $request): JsonResponse
    {
        $regionCode = $request->query('region');

        if ($regionCode) {
            $backups = $this->backupService->getBackupsByRegion($regionCode);
        } else {
            $backups = BackupCaregiver::orderedByPriority()->get();
        }

        return $this->success($backups);
    }

    /**
     * Adiciona cuidador ao banco de backup.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'caregiver_id' => 'required|integer',
            'region_code' => 'required|string|max:32',
            'priority' => 'nullable|integer|in:1,2,3',
            'available_from' => 'nullable|date_format:H:i',
            'available_until' => 'nullable|date_format:H:i',
            'service_types' => 'nullable|array',
            'service_types.*' => 'integer',
        ]);

        $backup = $this->backupService->addToBackup(
            $validated['caregiver_id'],
            $validated['region_code'],
            $validated['priority'] ?? BackupCaregiver::PRIORITY_MEDIUM,
            $validated['available_from'] ?? null,
            $validated['available_until'] ?? null,
            $validated['service_types'] ?? null
        );

        return $this->success($backup, 'Cuidador adicionado ao banco de backup.', 201);
    }

    /**
     * Remove cuidador do banco de backup.
     */
    public function destroy(int $caregiverId, Request $request): JsonResponse
    {
        $regionCode = $request->query('region');

        $deleted = $this->backupService->removeFromBackup($caregiverId, $regionCode);

        if ($deleted === 0) {
            return $this->notFound('Cuidador não encontrado no banco de backup.');
        }

        return $this->success(['deleted' => $deleted], 'Cuidador removido do banco de backup.');
    }

    /**
     * Atualiza disponibilidade do cuidador.
     */
    public function updateAvailability(Request $request, int $caregiverId): JsonResponse
    {
        $validated = $request->validate([
            'is_available' => 'required|boolean',
            'region_code' => 'nullable|string|max:32',
        ]);

        $updated = $this->backupService->setAvailability(
            $caregiverId,
            $validated['is_available'],
            $validated['region_code'] ?? null
        );

        return $this->success(['updated' => $updated], 'Disponibilidade atualizada.');
    }

    /**
     * Atualiza prioridade do cuidador.
     */
    public function updatePriority(Request $request, int $caregiverId): JsonResponse
    {
        $validated = $request->validate([
            'region_code' => 'required|string|max:32',
            'priority' => 'required|integer|in:1,2,3',
        ]);

        $backup = $this->backupService->updatePriority(
            $caregiverId,
            $validated['region_code'],
            $validated['priority']
        );

        if (!$backup) {
            return $this->notFound('Cuidador não encontrado no banco de backup.');
        }

        return $this->success($backup, 'Prioridade atualizada.');
    }

    /**
     * Busca cuidadores disponíveis para substituição.
     */
    public function findAvailable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region_code' => 'required|string|max:32',
            'service_type_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $backups = $this->backupService->findAvailableBackups(
            $validated['region_code'],
            $validated['service_type_id'] ?? null,
            $validated['limit'] ?? 5
        );

        return $this->success([
            'backups' => $backups,
            'count' => $backups->count(),
        ]);
    }

    /**
     * Busca melhor cuidador para substituição com expansão de região.
     */
    public function findBestWithExpansion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region_code' => 'required|string|max:32',
            'service_type_id' => 'nullable|integer',
            'nearby_regions' => 'nullable|array',
            'nearby_regions.*' => 'string|max:32',
        ]);

        $backup = $this->backupService->findBackupWithExpansion(
            $validated['region_code'],
            $validated['service_type_id'] ?? null,
            $validated['nearby_regions'] ?? []
        );

        if (!$backup) {
            return $this->success([
                'found' => false,
                'message' => 'Nenhum cuidador backup disponível na região.',
            ]);
        }

        return $this->success([
            'found' => true,
            'backup' => $backup,
        ]);
    }

    /**
     * Estatísticas do banco de backup.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->backupService->getBackupStats();

        return $this->success($stats);
    }

    /**
     * Lista regiões configuradas.
     */
    public function regions(): JsonResponse
    {
        $regions = $this->backupService->getConfiguredRegions();

        return $this->success($regions);
    }

    /**
     * Histórico de uso do banco de backup.
     */
    public function usageHistory(Request $request): JsonResponse
    {
        $regionCode = $request->query('region');
        $days = $request->query('days', 30);

        $history = $this->backupService->getUsageHistory($regionCode, (int) $days);

        return $this->success($history);
    }

    /**
     * Sincroniza com sistema de cuidadores.
     */
    public function sync(): JsonResponse
    {
        $result = $this->backupService->syncWithCuidadores();

        return $this->success($result, 'Sincronização concluída.');
    }
}
