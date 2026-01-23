<?php

namespace App\Services\Integrations;

/**
 * Integração com Carinho Cuidadores (cuidadores.carinho.com.vc)
 * Consulta disponibilidade e perfis de cuidadores
 */
class CarinhoCuidadoresService extends BaseInternalService
{
    protected string $serviceName = 'carinho-cuidadores';

    public function isEnabled(): bool
    {
        return config('integrations.internal.cuidadores.enabled', true) 
            && !empty($this->apiKey);
    }

    /**
     * Busca cuidadores disponíveis por critérios
     */
    public function searchAvailableCaregivers(array $criteria): ?array
    {
        return $this->get('caregivers/available', [
            'city' => $criteria['city'] ?? null,
            'patient_type' => $criteria['patient_type'] ?? null,
            'service_type' => $criteria['service_type'] ?? null,
            'start_date' => $criteria['start_date'] ?? null,
            'schedule' => $criteria['schedule'] ?? null,
        ]);
    }

    /**
     * Obtém detalhes de um cuidador
     */
    public function getCaregiverProfile(int $caregiverId): ?array
    {
        return $this->get("caregivers/{$caregiverId}");
    }

    /**
     * Verifica disponibilidade de um cuidador específico
     */
    public function checkCaregiverAvailability(int $caregiverId, string $startDate, ?string $endDate = null): ?array
    {
        return $this->get("caregivers/{$caregiverId}/availability", [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Obtém quantidade de cuidadores disponíveis por região
     */
    public function getAvailabilityByCity(string $city): ?array
    {
        return $this->get('caregivers/count-by-city', [
            'city' => $city,
        ]);
    }

    /**
     * Obtém cuidadores por especialidade
     */
    public function getCaregiversBySpecialty(string $specialty): ?array
    {
        return $this->get('caregivers/by-specialty', [
            'specialty' => $specialty,
        ]);
    }
}
