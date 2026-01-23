<?php

namespace App\Http\Controllers;

use App\Services\EmergencyService;
use App\Models\Emergency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de emergencias.
 */
class EmergencyController extends Controller
{
    public function __construct(
        protected EmergencyService $emergencyService
    ) {}

    /**
     * Lista emergencias.
     */
    public function index(Request $request): JsonResponse
    {
        $serviceRequestId = $request->query('service_request_id');
        $pending = $request->boolean('pending');

        $query = Emergency::with(['serviceRequest', 'severity'])
            ->when($serviceRequestId, fn($q) => $q->where('service_request_id', $serviceRequestId))
            ->when($pending, fn($q) => $q->pending())
            ->orderByRaw('FIELD(severity_id, 4, 3, 2, 1)')
            ->orderBy('id', 'desc');

        $emergencies = $query->paginate(20);

        return $this->success($emergencies);
    }

    /**
     * Exibe detalhes de uma emergencia.
     */
    public function show(int $id): JsonResponse
    {
        $emergency = Emergency::with(['serviceRequest', 'severity'])->find($id);

        if (!$emergency) {
            return $this->notFound('Emergencia nao encontrada.');
        }

        return $this->success($emergency);
    }

    /**
     * Registra nova emergencia.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_request_id' => 'required|integer|exists:service_requests,id',
            'severity_id' => 'required|integer|in:1,2,3,4',
            'description' => 'required|string|max:2000',
        ]);

        try {
            $emergency = $this->emergencyService->createEmergency(
                $validated['service_request_id'],
                $validated['severity_id'],
                $validated['description']
            );

            return $this->success($emergency, 'Emergencia registrada.', 201);
        } catch (\Throwable $e) {
            return $this->error('Erro ao registrar emergencia: ' . $e->getMessage());
        }
    }

    /**
     * Resolve emergencia.
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $emergency = Emergency::find($id);

        if (!$emergency) {
            return $this->notFound('Emergencia nao encontrada.');
        }

        if ($emergency->isResolved()) {
            return $this->error('Emergencia ja foi resolvida.');
        }

        $validated = $request->validate([
            'resolution' => 'nullable|string|max:2000',
        ]);

        try {
            $emergency = $this->emergencyService->resolveEmergency(
                $emergency,
                $validated['resolution'] ?? null
            );

            return $this->success($emergency, 'Emergencia resolvida.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao resolver emergencia: ' . $e->getMessage());
        }
    }

    /**
     * Escalona emergencia.
     */
    public function escalate(int $id): JsonResponse
    {
        $emergency = Emergency::find($id);

        if (!$emergency) {
            return $this->notFound('Emergencia nao encontrada.');
        }

        if ($emergency->isResolved()) {
            return $this->error('Emergencia ja foi resolvida.');
        }

        try {
            $emergency = $this->emergencyService->escalateEmergency($emergency);

            return $this->success($emergency, 'Emergencia escalonada.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao escalonar emergencia: ' . $e->getMessage());
        }
    }

    /**
     * Obtem emergencias pendentes.
     */
    public function pending(): JsonResponse
    {
        $emergencies = $this->emergencyService->getPendingEmergencies();

        return $this->success($emergencies);
    }

    /**
     * Obtem emergencias criticas.
     */
    public function critical(): JsonResponse
    {
        $emergencies = $this->emergencyService->getCriticalEmergencies();

        return $this->success($emergencies);
    }

    /**
     * Obtem estatisticas de emergencias.
     */
    public function stats(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $stats = $this->emergencyService->getEmergencyStats($startDate, $endDate);

        return $this->success($stats);
    }

    /**
     * Obtem tipos de emergencia.
     */
    public function types(): JsonResponse
    {
        $types = EmergencyService::commonEmergencyTypes();

        return $this->success($types);
    }
}
