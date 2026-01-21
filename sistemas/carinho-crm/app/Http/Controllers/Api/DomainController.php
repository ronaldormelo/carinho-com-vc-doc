<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain\DomainUrgencyLevel;
use App\Models\Domain\DomainServiceType;
use App\Models\Domain\DomainLeadStatus;
use App\Models\Domain\DomainDealStatus;
use App\Models\Domain\DomainContractStatus;
use App\Models\Domain\DomainInteractionChannel;
use App\Models\Domain\DomainPatientType;
use App\Models\Domain\DomainTaskStatus;
use App\Models\Consent;
use App\Models\LossReason;
use Illuminate\Support\Facades\Cache;

class DomainController extends Controller
{
    /**
     * Retorna todos os valores de domínio para uso em formulários
     */
    public function all()
    {
        $cacheKey = 'domains:all';
        $ttl = config('cache.ttl.domains', 3600);

        $domains = Cache::remember($cacheKey, $ttl, function () {
            return [
                'urgency_levels' => DomainUrgencyLevel::all(),
                'service_types' => DomainServiceType::all(),
                'lead_statuses' => DomainLeadStatus::all(),
                'deal_statuses' => DomainDealStatus::all(),
                'contract_statuses' => DomainContractStatus::all(),
                'interaction_channels' => DomainInteractionChannel::all(),
                'patient_types' => DomainPatientType::all(),
                'task_statuses' => DomainTaskStatus::all(),
                'consent_types' => Consent::availableTypes(),
                'consent_sources' => Consent::availableSources(),
                'loss_reasons' => LossReason::availableReasons(),
            ];
        });

        return $this->successResponse($domains);
    }

    /**
     * Níveis de urgência
     */
    public function urgencyLevels()
    {
        return $this->successResponse(DomainUrgencyLevel::allCached());
    }

    /**
     * Tipos de serviço
     */
    public function serviceTypes()
    {
        return $this->successResponse(DomainServiceType::allCached());
    }

    /**
     * Status de lead
     */
    public function leadStatuses()
    {
        return $this->successResponse(DomainLeadStatus::allCached());
    }

    /**
     * Status de deal
     */
    public function dealStatuses()
    {
        return $this->successResponse(DomainDealStatus::allCached());
    }

    /**
     * Status de contrato
     */
    public function contractStatuses()
    {
        return $this->successResponse(DomainContractStatus::allCached());
    }

    /**
     * Canais de interação
     */
    public function interactionChannels()
    {
        return $this->successResponse(DomainInteractionChannel::allCached());
    }

    /**
     * Tipos de paciente
     */
    public function patientTypes()
    {
        return $this->successResponse(DomainPatientType::allCached());
    }

    /**
     * Status de tarefa
     */
    public function taskStatuses()
    {
        return $this->successResponse(DomainTaskStatus::allCached());
    }

    /**
     * Tipos de consentimento
     */
    public function consentTypes()
    {
        return $this->successResponse(Consent::availableTypes());
    }

    /**
     * Motivos de perda
     */
    public function lossReasons()
    {
        return $this->successResponse(LossReason::availableReasons());
    }
}
