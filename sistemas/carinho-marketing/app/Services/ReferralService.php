<?php

namespace App\Services;

use App\Models\CustomerReferral;
use App\Models\ReferralProgramConfig;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de gestão de indicações de clientes.
 * 
 * Gerencia o programa de indicação onde clientes satisfeitos
 * podem indicar novos clientes e receber benefícios.
 */
class ReferralService
{
    /**
     * Cria código de indicação para cliente.
     */
    public function createReferralCode(
        string $customerId,
        string $customerName,
        ?string $customerPhone = null
    ): CustomerReferral {
        if (!ReferralProgramConfig::isProgramActive()) {
            throw new \Exception('O programa de indicação não está ativo.');
        }

        $referral = CustomerReferral::createForCustomer(
            $customerId,
            $customerName,
            $customerPhone
        );

        Log::info('Referral code created for customer', [
            'customer_id' => $customerId,
            'referral_code' => $referral->referral_code,
        ]);

        return $referral;
    }

    /**
     * Registra lead indicado.
     */
    public function registerReferred(
        string $referralCode,
        string $leadId,
        ?string $leadName = null,
        ?string $leadPhone = null
    ): ?CustomerReferral {
        if (!ReferralProgramConfig::isProgramActive()) {
            return null;
        }

        // Verifica se código existe
        $originalReferral = CustomerReferral::findByReferralCode($referralCode);
        if (!$originalReferral) {
            return null;
        }

        // Verifica se cliente pode indicar mais
        if (!ReferralProgramConfig::canReferMore($originalReferral->referrer_customer_id)) {
            Log::warning('Customer exceeded monthly referral limit', [
                'customer_id' => $originalReferral->referrer_customer_id,
                'referral_code' => $referralCode,
            ]);
            return null;
        }

        $referral = CustomerReferral::registerReferred(
            $referralCode,
            $leadId,
            $leadName,
            $leadPhone
        );

        if ($referral) {
            // Registra origem do lead
            LeadSource::create([
                'lead_id' => $leadId,
                'utm_source' => 'indicacao',
                'utm_medium' => 'referral',
                'utm_campaign' => 'programa_indicacao',
                'utm_content' => $referralCode,
                'captured_at' => now(),
            ]);

            Log::info('Referred lead registered', [
                'referral_code' => $referralCode,
                'lead_id' => $leadId,
                'referrer_customer_id' => $referral->referrer_customer_id,
            ]);
        }

        return $referral;
    }

    /**
     * Marca indicação como convertida.
     */
    public function markAsConverted(int $referralId, float $contractValue): CustomerReferral
    {
        $referral = CustomerReferral::findOrFail($referralId);

        // Verifica valor mínimo
        if (!ReferralProgramConfig::meetsMinimumValue($contractValue)) {
            throw new \Exception('Valor do contrato não atinge o mínimo para benefício.');
        }

        $referral->markAsConverted($contractValue);

        Log::info('Customer referral converted', [
            'referral_id' => $referralId,
            'contract_value' => $contractValue,
            'benefit_value' => $referral->benefit_value,
        ]);

        return $referral->fresh();
    }

    /**
     * Aplica benefício ao cliente que indicou.
     */
    public function applyBenefit(int $referralId): CustomerReferral
    {
        $referral = CustomerReferral::findOrFail($referralId);

        if (!$referral->converted) {
            throw new \Exception('Indicação ainda não foi convertida.');
        }

        if ($referral->benefit_applied) {
            throw new \Exception('Benefício já foi aplicado.');
        }

        $referral->applyBenefit();

        Log::info('Referral benefit applied', [
            'referral_id' => $referralId,
            'benefit_type' => $referral->benefit_type,
            'benefit_value' => $referral->benefit_value,
        ]);

        return $referral;
    }

    /**
     * Obtém informações de indicação do cliente.
     */
    public function getCustomerReferralInfo(string $customerId): array
    {
        // Obtém ou cria código de indicação
        $referral = CustomerReferral::where('referrer_customer_id', $customerId)
            ->whereNull('referred_lead_id')
            ->first();

        $programConfig = ReferralProgramConfig::getActive();

        return [
            'referral_code' => $referral?->referral_code,
            'total_referrals' => CustomerReferral::countByReferrer($customerId),
            'total_conversions' => CustomerReferral::countConversionsByReferrer($customerId),
            'can_refer_more' => ReferralProgramConfig::canReferMore($customerId),
            'program' => [
                'is_active' => $programConfig?->is_active ?? false,
                'benefit_type' => $programConfig?->benefit_type,
                'benefit_value' => $programConfig?->referrer_benefit_value,
                'max_per_month' => $programConfig?->max_referrals_per_month,
            ],
        ];
    }

    /**
     * Lista indicações do cliente.
     */
    public function listCustomerReferrals(string $customerId): array
    {
        return CustomerReferral::byReferrer($customerId)
            ->whereNotNull('referred_lead_id')
            ->orderBy('referred_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Lista benefícios pendentes.
     */
    public function listPendingBenefits(): array
    {
        return CustomerReferral::benefitPending()
            ->orderBy('converted_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Obtém estatísticas do programa de indicação.
     */
    public function getStats(string $startDate, string $endDate): array
    {
        $referrals = CustomerReferral::inPeriod($startDate, $endDate)
            ->whereNotNull('referred_lead_id');

        return [
            'program_active' => ReferralProgramConfig::isProgramActive(),
            'total_referrals' => (clone $referrals)->count(),
            'converted_referrals' => (clone $referrals)->converted()->count(),
            'conversion_rate' => $this->calculateConversionRate(
                (clone $referrals)->count(),
                (clone $referrals)->converted()->count()
            ),
            'total_contract_value' => (clone $referrals)->converted()->sum('contract_value') ?? 0,
            'total_benefits_given' => (clone $referrals)
                ->where('benefit_applied', true)
                ->sum('benefit_value') ?? 0,
            'pending_benefits' => CustomerReferral::benefitPending()->count(),
            'unique_referrers' => (clone $referrals)
                ->distinct('referrer_customer_id')
                ->count('referrer_customer_id'),
        ];
    }

    /**
     * Calcula taxa de conversão.
     */
    private function calculateConversionRate(int $total, int $converted): ?float
    {
        if ($total === 0) {
            return null;
        }

        return round(($converted / $total) * 100, 2);
    }

    /**
     * Atualiza configuração do programa.
     */
    public function updateProgramConfig(array $config): ReferralProgramConfig
    {
        $programConfig = ReferralProgramConfig::getActive() ?? new ReferralProgramConfig();

        $programConfig->fill([
            'is_active' => $config['is_active'] ?? $programConfig->is_active,
            'benefit_type' => $config['benefit_type'] ?? $programConfig->benefit_type,
            'referrer_benefit_value' => $config['referrer_benefit_value'] ?? $programConfig->referrer_benefit_value,
            'referred_benefit_value' => $config['referred_benefit_value'] ?? $programConfig->referred_benefit_value,
            'min_contract_value' => $config['min_contract_value'] ?? $programConfig->min_contract_value,
            'max_referrals_per_month' => $config['max_referrals_per_month'] ?? $programConfig->max_referrals_per_month,
            'terms' => $config['terms'] ?? $programConfig->terms,
        ]);

        $programConfig->save();

        return $programConfig;
    }

    /**
     * Obtém configuração do programa.
     */
    public function getProgramConfig(): ?ReferralProgramConfig
    {
        return ReferralProgramConfig::getActive();
    }
}
