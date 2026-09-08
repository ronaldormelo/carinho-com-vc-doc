<?php

namespace App\Services\Integrations\Operacao;

use App\Services\Integrations\BaseClient;

/**
 * Cliente da Operação. Rotas reais: /api/schedules, /api/checkin/..., /api/assignments/..., /api/service-requests.
 */
class OperacaoClient extends BaseClient
{
    protected string $configKey = 'operacao';

    public function createSchedule(array $data): array
    {
        return $this->post('/api/schedules', $data);
    }

    public function updateSchedule(int $scheduleId, array $data): array
    {
        return $this->unsupported("PUT agenda {$scheduleId}");
    }

    public function getSchedule(int $scheduleId): array
    {
        return $this->get("/api/schedules/{$scheduleId}");
    }

    public function getClientSchedules(int $clientId, ?string $status = null): array
    {
        $query = ['client_id' => $clientId];

        if ($status) {
            $query['status'] = $status;
        }

        return $this->get('/api/schedules', $query);
    }

    public function cancelSchedule(int $scheduleId, string $reason): array
    {
        return $this->post("/api/schedules/{$scheduleId}/cancel", [
            'reason' => $reason,
        ]);
    }

    public function getService(int $serviceId): array
    {
        return $this->get("/api/service-requests/{$serviceId}");
    }

    public function checkIn(int $serviceId, array $data): array
    {
        return $this->post("/api/checkin/schedule/{$serviceId}/in", [
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
        ]);
    }

    public function checkOut(int $serviceId, array $data): array
    {
        return $this->post("/api/checkin/schedule/{$serviceId}/out", [
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? '',
            'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
        ]);
    }

    public function getTodayServices(): array
    {
        return $this->get('/api/schedules/today');
    }

    public function getPendingServices(): array
    {
        return $this->get('/api/service-requests/open');
    }

    public function getCompletedServices(string $startDate, string $endDate): array
    {
        return $this->get('/api/schedules', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function findAvailableCaregivers(array $criteria): array
    {
        $serviceRequestId = $criteria['service_request_id'] ?? $criteria['id'] ?? null;

        if (!$serviceRequestId) {
            return $this->unsupported('busca de alocação sem service_request_id');
        }

        return $this->get("/api/assignments/service-request/{$serviceRequestId}/candidates", $criteria);
    }

    public function allocateCaregiver(int $serviceRequestId, int $caregiverId): array
    {
        return $this->post("/api/assignments/service-request/{$serviceRequestId}/assign", [
            'caregiver_id' => $caregiverId,
        ]);
    }

    public function reallocateCaregiver(int $assignmentId, int $newCaregiverId, string $reason): array
    {
        return $this->post("/api/assignments/{$assignmentId}/substitute", [
            'caregiver_id' => $newCaregiverId,
            'reason' => $reason,
        ]);
    }

    public function reportIncident(int $serviceId, array $data): array
    {
        return $this->post('/api/emergencies', [
            'service_request_id' => $data['service_request_id'] ?? $serviceId,
            'severity_id' => $data['severity_id'] ?? 2,
            'description' => $data['description'] ?? $data['notes'] ?? 'Incidente operacional',
        ]);
    }

    public function getServiceIncidents(int $serviceId): array
    {
        return $this->get('/api/emergencies', ['service_request_id' => $serviceId]);
    }

    public function registerFeedback(int $serviceId, array $data): array
    {
        return $this->unsupported("feedback do serviço {$serviceId}");
    }

    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->post('/api/webhooks/atendimento', [
            'event' => $eventType,
            'data' => $payload,
        ]);
    }
}
