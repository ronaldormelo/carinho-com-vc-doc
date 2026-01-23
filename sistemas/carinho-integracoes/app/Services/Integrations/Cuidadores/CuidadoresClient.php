<?php

namespace App\Services\Integrations\Cuidadores;

use App\Services\Integrations\BaseClient;

/**
 * Cliente para integracao com o sistema de Cuidadores (cuidadores.carinho.com.vc).
 *
 * Responsavel por:
 * - Consulta de disponibilidade
 * - Dados cadastrais de cuidadores
 * - Avaliacoes e ratings
 * - Notificacoes para cuidadores
 */
class CuidadoresClient extends BaseClient
{
    protected string $configKey = 'cuidadores';

    /*
    |--------------------------------------------------------------------------
    | Cuidadores
    |--------------------------------------------------------------------------
    */

    /**
     * Busca cuidador por ID.
     */
    public function getCaregiver(int $caregiverId): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}");
    }

    /**
     * Lista cuidadores com filtros.
     */
    public function listCaregivers(array $filters = []): array
    {
        return $this->get('/api/v1/caregivers', $filters);
    }

    /**
     * Busca cuidadores disponiveis.
     */
    public function findAvailable(array $criteria): array
    {
        return $this->post('/api/v1/caregivers/search', $criteria);
    }

    /**
     * Atualiza dados do cuidador.
     */
    public function updateCaregiver(int $caregiverId, array $data): array
    {
        return $this->put("/api/v1/caregivers/{$caregiverId}", $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Disponibilidade
    |--------------------------------------------------------------------------
    */

    /**
     * Busca disponibilidade do cuidador.
     */
    public function getAvailability(int $caregiverId, string $startDate, string $endDate): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}/availability", [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Atualiza disponibilidade.
     */
    public function updateAvailability(int $caregiverId, array $slots): array
    {
        return $this->put("/api/v1/caregivers/{$caregiverId}/availability", [
            'slots' => $slots,
        ]);
    }

    /**
     * Bloqueia horario (alocacao).
     */
    public function blockSlot(int $caregiverId, array $data): array
    {
        return $this->post("/api/v1/caregivers/{$caregiverId}/availability/block", $data);
    }

    /**
     * Libera horario bloqueado.
     */
    public function releaseSlot(int $caregiverId, int $slotId): array
    {
        return $this->delete("/api/v1/caregivers/{$caregiverId}/availability/{$slotId}");
    }

    /*
    |--------------------------------------------------------------------------
    | Avaliacoes
    |--------------------------------------------------------------------------
    */

    /**
     * Registra avaliacao do cuidador.
     */
    public function registerRating(int $caregiverId, array $data): array
    {
        return $this->post("/api/v1/caregivers/{$caregiverId}/ratings", [
            'service_id' => $data['service_id'],
            'client_id' => $data['client_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? '',
            'categories' => $data['categories'] ?? [],
        ]);
    }

    /**
     * Busca avaliacoes do cuidador.
     */
    public function getRatings(int $caregiverId): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}/ratings");
    }

    /**
     * Busca media de avaliacao.
     */
    public function getAverageRating(int $caregiverId): array
    {
        return $this->getCached("/api/v1/caregivers/{$caregiverId}/ratings/average");
    }

    /*
    |--------------------------------------------------------------------------
    | Contratos e Documentos
    |--------------------------------------------------------------------------
    */

    /**
     * Busca contratos do cuidador.
     */
    public function getContracts(int $caregiverId): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}/contracts");
    }

    /**
     * Busca documentos do cuidador.
     */
    public function getDocuments(int $caregiverId): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}/documents");
    }

    /**
     * Verifica se documentacao esta completa e valida.
     */
    public function isDocumentationValid(int $caregiverId): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}/documents/status");
    }

    /*
    |--------------------------------------------------------------------------
    | Habilidades e Regioes
    |--------------------------------------------------------------------------
    */

    /**
     * Busca habilidades do cuidador.
     */
    public function getSkills(int $caregiverId): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}/skills");
    }

    /**
     * Busca regioes de atuacao.
     */
    public function getRegions(int $caregiverId): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}/regions");
    }

    /*
    |--------------------------------------------------------------------------
    | Notificacoes
    |--------------------------------------------------------------------------
    */

    /**
     * Envia notificacao para cuidador.
     */
    public function sendNotification(int $caregiverId, array $data): array
    {
        return $this->post("/api/v1/caregivers/{$caregiverId}/notifications", $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Incidentes
    |--------------------------------------------------------------------------
    */

    /**
     * Registra incidente do cuidador.
     */
    public function registerIncident(int $caregiverId, array $data): array
    {
        return $this->post("/api/v1/caregivers/{$caregiverId}/incidents", $data);
    }

    /**
     * Lista incidentes do cuidador.
     */
    public function getIncidents(int $caregiverId): array
    {
        return $this->get("/api/v1/caregivers/{$caregiverId}/incidents");
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Dispara evento para sistema de Cuidadores.
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
