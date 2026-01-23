<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverIncident;
use App\Jobs\SyncIncidentWithCrm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IncidentController extends Controller
{
    /**
     * Lista ocorrencias de um cuidador.
     */
    public function index(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        $incidents = $caregiver->incidents()
            ->orderBy('occurred_at', 'desc')
            ->paginate($perPage);

        return $this->paginated($incidents, 'Ocorrencias carregadas');
    }

    /**
     * Registra nova ocorrencia.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer',
            'incident_type' => 'required|string|max:128',
            'notes' => 'required|string|max:2000',
            'occurred_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        $incident = CaregiverIncident::create([
            'caregiver_id' => $caregiver->id,
            'service_id' => $request->get('service_id'),
            'incident_type' => $request->get('incident_type'),
            'notes' => $request->get('notes'),
            'occurred_at' => $request->get('occurred_at', now()),
        ]);

        // Sincroniza com CRM
        SyncIncidentWithCrm::dispatch($incident);

        return $this->success($incident, 'Ocorrencia registrada com sucesso', 201);
    }

    /**
     * Exibe ocorrencia especifica.
     */
    public function show(int $caregiverId, int $incidentId): JsonResponse
    {
        $incident = CaregiverIncident::where('caregiver_id', $caregiverId)
            ->where('id', $incidentId)
            ->first();

        if (!$incident) {
            return $this->error('Ocorrencia nao encontrada', 404);
        }

        return $this->success($incident);
    }

    /**
     * Atualiza ocorrencia.
     */
    public function update(Request $request, int $caregiverId, int $incidentId): JsonResponse
    {
        $incident = CaregiverIncident::where('caregiver_id', $caregiverId)
            ->where('id', $incidentId)
            ->first();

        if (!$incident) {
            return $this->error('Ocorrencia nao encontrada', 404);
        }

        $validator = Validator::make($request->all(), [
            'incident_type' => 'sometimes|string|max:128',
            'notes' => 'sometimes|string|max:2000',
            'occurred_at' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        $incident->update($validator->validated());

        return $this->success($incident, 'Ocorrencia atualizada');
    }

    /**
     * Lista tipos de ocorrencia disponiveis.
     */
    public function types(): JsonResponse
    {
        return $this->success([
            'types' => CaregiverIncident::TYPES,
        ]);
    }

    /**
     * Lista todas as ocorrencias recentes (admin).
     */
    public function recent(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 7), 90);
        $perPage = min((int) $request->get('per_page', 20), 100);

        $incidents = CaregiverIncident::with('caregiver')
            ->recent($days)
            ->orderBy('occurred_at', 'desc')
            ->paginate($perPage);

        return $this->paginated($incidents, 'Ocorrencias recentes carregadas');
    }

    /**
     * Estatisticas de ocorrencias.
     */
    public function stats(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 30), 365);

        $stats = [
            'total' => CaregiverIncident::recent($days)->count(),
            'by_type' => CaregiverIncident::recent($days)
                ->selectRaw('incident_type, COUNT(*) as count')
                ->groupBy('incident_type')
                ->pluck('count', 'incident_type'),
            'caregivers_with_incidents' => CaregiverIncident::recent($days)
                ->distinct('caregiver_id')
                ->count('caregiver_id'),
        ];

        return $this->success($stats, 'Estatisticas de ocorrencias');
    }
}
