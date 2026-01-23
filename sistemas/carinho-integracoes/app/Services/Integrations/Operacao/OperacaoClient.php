<?php

namespace App\Services\Integrations\Operacao;

use App\Services\Integrations\BaseClient;

/**
 * Cliente para integracao com o sistema de Operacao (operacao.carinho.com.vc).
 *
 * Responsavel por:
 * - Gerenciamento de agenda e agendamentos
 * - Alocacao de cuidadores
 * - Check-in/out de servicos
 * - Notificacoes de inicio/fim
 */
class OperacaoClient extends BaseClient
{
    protected string $configKey = 'operacao';

    /*
    |--------------------------------------------------------------------------
    | Agendamentos
    |--------------------------------------------------------------------------
    */

    /**
     * Cria novo agendamento.
     */
    public function createSchedule(array $data): array
    {
        return $this->post('/api/v1/schedules', $data);
    }

    /**
     * Atualiza agendamento existente.
     */
    public function updateSchedule(int $scheduleId, array $data): array
    {
        return $this->put("/api/v1/schedules/{$scheduleId}", $data);
    }

    /**
     * Busca agendamento por ID.
     */
    public function getSchedule(int $scheduleId): array
    {
        return $this->get("/api/v1/schedules/{$scheduleId}");
    }

    /**
     * Lista agendamentos do cliente.
     */
    public function getClientSchedules(int $clientId, ?string $status = null): array
    {
        $query = ['client_id' => $clientId];

        if ($status) {
            $query['status'] = $status;
        }

        return $this->get('/api/v1/schedules', $query);
    }

    /**
     * Cancela agendamento.
     */
    public function cancelSchedule(int $scheduleId, string $reason): array
    {
        return $this->post("/api/v1/schedules/{$scheduleId}/cancel", [
            'reason' => $reason,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Servicos
    |--------------------------------------------------------------------------
    */

    /**
     * Busca servico por ID.
     */
    public function getService(int $serviceId): array
    {
        return $this->get("/api/v1/services/{$serviceId}");
    }

    /**
     * Registra check-in do cuidador.
     */
    public function checkIn(int $serviceId, array $data): array
    {
        return $this->post("/api/v1/services/{$serviceId}/check-in", [
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Registra check-out do cuidador.
     */
    public function checkOut(int $serviceId, array $data): array
    {
        return $this->post("/api/v1/services/{$serviceId}/check-out", [
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? '',
            'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Lista servicos do dia.
     */
    public function getTodayServices(): array
    {
        return $this->get('/api/v1/services/today');
    }

    /**
     * Lista servicos pendentes.
     */
    public function getPendingServices(): array
    {
        return $this->get('/api/v1/services', ['status' => 'pending']);
    }

    /**
     * Lista servicos finalizados (para calculo de pagamento).
     */
    public function getCompletedServices(string $startDate, string $endDate): array
    {
        return $this->get('/api/v1/services', [
            'status' => 'completed',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Alocacao de Cuidadores
    |--------------------------------------------------------------------------
    */

    /**
     * Busca cuidadores disponiveis para um horario.
     */
    public function findAvailableCaregivers(array $criteria): array
    {
        return $this->post('/api/v1/allocation/search', $criteria);
    }

    /**
     * Aloca cuidador para agendamento.
     */
    public function allocateCaregiver(int $scheduleId, int $caregiverId): array
    {
        return $this->post("/api/v1/schedules/{$scheduleId}/allocate", [
            'caregiver_id' => $caregiverId,
        ]);
    }

    /**
     * Realoca cuidador (substituicao).
     */
    public function reallocateCaregiver(int $scheduleId, int $newCaregiverId, string $reason): array
    {
        return $this->post("/api/v1/schedules/{$scheduleId}/reallocate", [
            'caregiver_id' => $newCaregiverId,
            'reason' => $reason,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Incidentes
    |--------------------------------------------------------------------------
    */

    /**
     * Registra incidente durante servico.
     */
    public function reportIncident(int $serviceId, array $data): array
    {
        return $this->post("/api/v1/services/{$serviceId}/incidents", $data);
    }

    /**
     * Lista incidentes de um servico.
     */
    public function getServiceIncidents(int $serviceId): array
    {
        return $this->get("/api/v1/services/{$serviceId}/incidents");
    }

    /*
    |--------------------------------------------------------------------------
    | Feedback
    |--------------------------------------------------------------------------
    */

    /**
     * Registra feedback do cliente.
     */
    public function registerFeedback(int $serviceId, array $data): array
    {
        return $this->post("/api/v1/services/{$serviceId}/feedback", [
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? '',
            'would_recommend' => $data['would_recommend'] ?? true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Dispara evento para Operacao.
     */
    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->post('/api/v1/webhooks/events', [
            'event_type' => $eventType,
            'payload' => $payload,
            'source' => 'integracoes',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
