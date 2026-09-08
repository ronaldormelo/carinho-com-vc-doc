<?php

namespace App\Services\Integrations\Cuidadores;

use App\Services\Integrations\BaseClient;

/**
 * Cliente de Cuidadores. Rotas reais: /api/caregivers, /api/search (sem /v1).
 */
class CuidadoresClient extends BaseClient
{
    protected string $configKey = 'cuidadores';

    public function getCaregiver(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}");
    }

    public function listCaregivers(array $filters = []): array
    {
        return $this->get('/api/caregivers', $filters);
    }

    public function findAvailable(array $criteria): array
    {
        return $this->post('/api/search', $criteria);
    }

    public function updateCaregiver(int $caregiverId, array $data): array
    {
        return $this->put("/api/caregivers/{$caregiverId}", $data);
    }

    public function getAvailability(int $caregiverId, string $startDate, string $endDate): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/availability", [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function updateAvailability(int $caregiverId, array $slots): array
    {
        return $this->put("/api/caregivers/{$caregiverId}/availability/sync", [
            'slots' => $slots,
        ]);
    }

    public function blockSlot(int $caregiverId, array $data): array
    {
        return $this->post("/api/caregivers/{$caregiverId}/availability", $data);
    }

    public function releaseSlot(int $caregiverId, int $slotId): array
    {
        return $this->delete("/api/caregivers/{$caregiverId}/availability/{$slotId}");
    }

    public function registerRating(int $caregiverId, array $data): array
    {
        return $this->post("/api/caregivers/{$caregiverId}/ratings", [
            'service_id' => $data['service_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'rating' => $data['rating'] ?? null,
            'comment' => $data['comment'] ?? '',
            'categories' => $data['categories'] ?? [],
        ]);
    }

    public function getRatings(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/ratings");
    }

    public function getAverageRating(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/ratings-summary");
    }

    public function getContracts(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/contracts");
    }

    public function getDocuments(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/documents");
    }

    public function isDocumentationValid(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/eligibility");
    }

    public function getSkills(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/skills");
    }

    public function getRegions(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/regions");
    }

    public function sendNotification(int $caregiverId, array $data): array
    {
        return $this->unsupported("notificação ao cuidador {$caregiverId}");
    }

    public function registerIncident(int $caregiverId, array $data): array
    {
        return $this->post("/api/caregivers/{$caregiverId}/incidents", $data);
    }

    public function getIncidents(int $caregiverId): array
    {
        return $this->get("/api/caregivers/{$caregiverId}/incidents");
    }

    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->post('/api/webhooks/operacao', array_merge($payload, [
            'event' => $eventType,
        ]));
    }
}
