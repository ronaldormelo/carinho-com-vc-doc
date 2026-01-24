<?php

namespace App\Services;

use App\Models\MarketingPartnership;
use App\Models\PartnershipReferral;
use App\Models\LeadSource;
use App\Models\Domain\DomainPartnershipStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de gestão de parcerias locais.
 * 
 * Gerencia parcerias com clínicas, hospitais, cuidadores
 * e outros estabelecimentos para geração de indicações.
 */
class PartnershipService
{
    /**
     * Lista parcerias com filtros.
     */
    public function list(array $filters = []): array
    {
        $query = MarketingPartnership::with(['type', 'status']);

        if (!empty($filters['type_id'])) {
            $query->ofType($filters['type_id']);
        }

        if (!empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (!empty($filters['city'])) {
            $query->inCity($filters['city']);
        }

        if (isset($filters['active']) && $filters['active']) {
            $query->active();
        }

        return $query->orderBy('name')->get()->toArray();
    }

    /**
     * Obtém parceria por ID.
     */
    public function get(int $id): array
    {
        $partnership = MarketingPartnership::with(['type', 'status'])
            ->findOrFail($id);

        return [
            'partnership' => $partnership->toArray(),
            'stats' => [
                'total_referrals' => $partnership->getTotalReferrals(),
                'converted_referrals' => $partnership->getConvertedReferrals(),
                'conversion_rate' => $partnership->getConversionRate(),
                'total_contract_value' => $partnership->getTotalContractValue(),
                'pending_commission' => $partnership->getPendingCommission(),
            ],
        ];
    }

    /**
     * Cria nova parceria.
     */
    public function create(array $data): MarketingPartnership
    {
        $partnership = MarketingPartnership::create([
            'name' => $data['name'],
            'type_id' => $data['type_id'],
            'status_id' => $data['status_id'] ?? DomainPartnershipStatus::ACTIVE,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'notes' => $data['notes'] ?? null,
            'commission_percent' => $data['commission_percent'] ?? null,
        ]);

        Log::info('Partnership created', [
            'id' => $partnership->id,
            'name' => $partnership->name,
            'type_id' => $partnership->type_id,
        ]);

        return $partnership->load(['type', 'status']);
    }

    /**
     * Atualiza parceria.
     */
    public function update(int $id, array $data): MarketingPartnership
    {
        $partnership = MarketingPartnership::findOrFail($id);

        $partnership->update(array_filter([
            'name' => $data['name'] ?? null,
            'type_id' => $data['type_id'] ?? null,
            'status_id' => $data['status_id'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'notes' => $data['notes'] ?? null,
            'commission_percent' => $data['commission_percent'] ?? null,
        ], fn ($v) => $v !== null));

        return $partnership->fresh(['type', 'status']);
    }

    /**
     * Ativa parceria.
     */
    public function activate(int $id): MarketingPartnership
    {
        $partnership = MarketingPartnership::findOrFail($id);
        $partnership->update(['status_id' => DomainPartnershipStatus::ACTIVE]);
        return $partnership->fresh(['type', 'status']);
    }

    /**
     * Desativa parceria.
     */
    public function deactivate(int $id): MarketingPartnership
    {
        $partnership = MarketingPartnership::findOrFail($id);
        $partnership->update(['status_id' => DomainPartnershipStatus::INACTIVE]);
        return $partnership->fresh(['type', 'status']);
    }

    /**
     * Registra indicação de parceiro.
     */
    public function registerReferral(
        string $referralCode,
        string $leadId,
        ?string $leadName = null,
        ?string $leadPhone = null
    ): ?PartnershipReferral {
        $partnership = MarketingPartnership::findByReferralCode($referralCode);

        if (!$partnership || !$partnership->isActive()) {
            return null;
        }

        $referral = PartnershipReferral::registerReferral(
            $partnership->id,
            $leadId,
            $leadName,
            $leadPhone
        );

        // Registra origem do lead
        LeadSource::create([
            'lead_id' => $leadId,
            'utm_source' => 'parceria',
            'utm_medium' => 'referral',
            'utm_campaign' => $partnership->name,
            'utm_content' => $referralCode,
            'captured_at' => now(),
        ]);

        Log::info('Partnership referral registered', [
            'partnership_id' => $partnership->id,
            'lead_id' => $leadId,
            'referral_code' => $referralCode,
        ]);

        return $referral;
    }

    /**
     * Marca indicação como convertida.
     */
    public function markReferralConverted(
        int $referralId,
        float $contractValue
    ): PartnershipReferral {
        $referral = PartnershipReferral::findOrFail($referralId);
        
        $referral->markAsConverted($contractValue);

        Log::info('Partnership referral converted', [
            'referral_id' => $referralId,
            'contract_value' => $contractValue,
            'commission_value' => $referral->commission_value,
        ]);

        return $referral->fresh();
    }

    /**
     * Marca comissão como paga.
     */
    public function markCommissionPaid(int $referralId): PartnershipReferral
    {
        $referral = PartnershipReferral::findOrFail($referralId);
        return $referral->markCommissionPaid();
    }

    /**
     * Lista indicações de um parceiro.
     */
    public function listReferrals(int $partnershipId, array $filters = []): array
    {
        $query = PartnershipReferral::where('partnership_id', $partnershipId);

        if (isset($filters['converted'])) {
            if ($filters['converted']) {
                $query->converted();
            } else {
                $query->pending();
            }
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->inPeriod($filters['start_date'], $filters['end_date']);
        }

        return $query->orderBy('referred_at', 'desc')->get()->toArray();
    }

    /**
     * Lista comissões pendentes.
     */
    public function listPendingCommissions(): array
    {
        return PartnershipReferral::with('partnership')
            ->commissionPending()
            ->orderBy('converted_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Obtém estatísticas de parcerias.
     */
    public function getStats(string $startDate, string $endDate): array
    {
        $referrals = PartnershipReferral::inPeriod($startDate, $endDate);

        return [
            'total_partnerships' => MarketingPartnership::active()->count(),
            'total_referrals' => $referrals->count(),
            'converted_referrals' => (clone $referrals)->converted()->count(),
            'total_contract_value' => (clone $referrals)->converted()->sum('contract_value') ?? 0,
            'total_commission' => (clone $referrals)->converted()->sum('commission_value') ?? 0,
            'pending_commission' => PartnershipReferral::commissionPending()->sum('commission_value') ?? 0,
            'by_type' => $this->getStatsByType($startDate, $endDate),
        ];
    }

    /**
     * Estatísticas por tipo de parceria.
     */
    private function getStatsByType(string $startDate, string $endDate): array
    {
        return DB::table('partnership_referrals')
            ->join('marketing_partnerships', 'partnership_referrals.partnership_id', '=', 'marketing_partnerships.id')
            ->join('domain_partnership_type', 'marketing_partnerships.type_id', '=', 'domain_partnership_type.id')
            ->whereBetween('partnership_referrals.referred_at', [$startDate, $endDate])
            ->groupBy('domain_partnership_type.id', 'domain_partnership_type.label')
            ->select(
                'domain_partnership_type.label as type',
                DB::raw('COUNT(*) as total_referrals'),
                DB::raw('SUM(CASE WHEN partnership_referrals.converted = 1 THEN 1 ELSE 0 END) as converted'),
                DB::raw('SUM(CASE WHEN partnership_referrals.converted = 1 THEN partnership_referrals.contract_value ELSE 0 END) as total_value')
            )
            ->get()
            ->toArray();
    }
}
