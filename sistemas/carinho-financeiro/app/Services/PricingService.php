<?php

namespace App\Services;

use App\Models\DomainServiceType;
use App\Models\PricePlan;
use App\Models\PriceRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Serviço de Precificação.
 *
 * Responsável por calcular preços baseados em:
 * - Tipo de serviço (horista, diário, mensal)
 * - Quantidade de horas/dias
 * - Adicionais (noturno, fim de semana, feriados)
 * - Descontos (pacotes, fidelidade)
 */
class PricingService
{
    /**
     * Calcula o preço total de um serviço.
     */
    public function calculatePrice(array $params): array
    {
        $serviceTypeId = $params['service_type_id'];
        $qty = (float) $params['qty']; // horas ou dias
        $startTime = isset($params['start_time']) ? Carbon::parse($params['start_time']) : null;
        $endTime = isset($params['end_time']) ? Carbon::parse($params['end_time']) : null;
        $regionCode = $params['region_code'] ?? null;
        $isWeekend = $params['is_weekend'] ?? false;
        $isHoliday = $params['is_holiday'] ?? false;
        $isMonthlyPackage = $params['is_monthly_package'] ?? false;

        // Busca plano de preço aplicável
        $plan = $this->findApplicablePlan($serviceTypeId, $regionCode);
        
        if (!$plan) {
            // Usa preço base da configuração
            $basePrice = $this->getConfigBasePrice($serviceTypeId);
        } else {
            $basePrice = (float) $plan->base_price;
        }

        // Calcula preço base
        $subtotal = $basePrice * $qty;
        $adjustments = [];

        // Adicional noturno (22h às 6h)
        if ($startTime && $this->hasNightHours($startTime, $endTime)) {
            $nightSurcharge = config('financeiro.pricing.night_surcharge', 20);
            $nightAmount = $subtotal * ($nightSurcharge / 100);
            $adjustments[] = [
                'type' => 'night_surcharge',
                'label' => 'Adicional noturno',
                'percent' => $nightSurcharge,
                'amount' => $nightAmount,
            ];
            $subtotal += $nightAmount;
        }

        // Adicional fim de semana
        if ($isWeekend) {
            $weekendSurcharge = config('financeiro.pricing.weekend_surcharge', 30);
            $weekendAmount = ($basePrice * $qty) * ($weekendSurcharge / 100);
            $adjustments[] = [
                'type' => 'weekend_surcharge',
                'label' => 'Adicional fim de semana',
                'percent' => $weekendSurcharge,
                'amount' => $weekendAmount,
            ];
            $subtotal += $weekendAmount;
        }

        // Adicional feriado
        if ($isHoliday) {
            $holidaySurcharge = config('financeiro.pricing.holiday_surcharge', 50);
            $holidayAmount = ($basePrice * $qty) * ($holidaySurcharge / 100);
            $adjustments[] = [
                'type' => 'holiday_surcharge',
                'label' => 'Adicional feriado',
                'percent' => $holidaySurcharge,
                'amount' => $holidayAmount,
            ];
            $subtotal += $holidayAmount;
        }

        // Desconto pacote mensal
        if ($isMonthlyPackage) {
            $monthlyDiscount = config('financeiro.pricing.monthly_discount', 10);
            $discountAmount = $subtotal * ($monthlyDiscount / 100);
            $adjustments[] = [
                'type' => 'monthly_discount',
                'label' => 'Desconto pacote mensal',
                'percent' => -$monthlyDiscount,
                'amount' => -$discountAmount,
            ];
            $subtotal -= $discountAmount;
        }

        // Garante preço mínimo
        $minHourly = config('financeiro.pricing.minimum_hourly', 35);
        $minTotal = $minHourly * $qty;
        $total = max($subtotal, $minTotal);

        // Calcula comissões
        $serviceType = DomainServiceType::find($serviceTypeId);
        $caregiverPercent = $serviceType?->getCaregiverCommissionPercent() 
            ?? config('financeiro.commission.caregiver_percent', 70);
        
        $caregiverAmount = round($total * ($caregiverPercent / 100), 2);
        $companyAmount = round($total - $caregiverAmount, 2);

        return [
            'service_type_id' => $serviceTypeId,
            'qty' => $qty,
            'unit_price' => round($basePrice, 2),
            'subtotal' => round($basePrice * $qty, 2),
            'adjustments' => $adjustments,
            'total' => round($total, 2),
            'breakdown' => [
                'caregiver_percent' => $caregiverPercent,
                'caregiver_amount' => $caregiverAmount,
                'company_percent' => 100 - $caregiverPercent,
                'company_amount' => $companyAmount,
            ],
            'minimum_applied' => $total === $minTotal && $subtotal < $minTotal,
        ];
    }

    /**
     * Simula preços para todos os tipos de serviço.
     */
    public function simulatePrices(float $hours, array $options = []): array
    {
        $results = [];
        $serviceTypes = DomainServiceType::all();

        foreach ($serviceTypes as $type) {
            $params = array_merge([
                'service_type_id' => $type->id,
                'qty' => $hours,
            ], $options);

            $results[$type->code] = $this->calculatePrice($params);
        }

        return $results;
    }

    /**
     * Busca plano de preço aplicável.
     */
    protected function findApplicablePlan(int $serviceTypeId, ?string $regionCode): ?PricePlan
    {
        $cacheKey = "price_plan_{$serviceTypeId}_{$regionCode}";

        return Cache::remember($cacheKey, 3600, function () use ($serviceTypeId, $regionCode) {
            // Primeiro tenta encontrar plano específico da região
            $plan = PricePlan::active()
                ->byServiceType($serviceTypeId)
                ->byRegion($regionCode)
                ->first();

            // Se não encontrar, busca plano padrão (sem região)
            if (!$plan && $regionCode) {
                $plan = PricePlan::active()
                    ->byServiceType($serviceTypeId)
                    ->byRegion(null)
                    ->first();
            }

            return $plan;
        });
    }

    /**
     * Obtém preço base da configuração.
     */
    protected function getConfigBasePrice(int $serviceTypeId): float
    {
        $serviceType = DomainServiceType::find($serviceTypeId);
        
        if (!$serviceType) {
            return config('financeiro.pricing.minimum_hourly', 35);
        }

        $pricing = config('financeiro.pricing.base');
        
        return match ($serviceType->code) {
            'horista' => $pricing['horista']['price_per_hour'] ?? 50,
            'diario' => ($pricing['diario']['price_per_day'] ?? 300) / ($pricing['diario']['hours_per_day'] ?? 12),
            'mensal' => $this->calculateMonthlyHourlyRate($pricing['mensal'] ?? []),
            default => config('financeiro.pricing.minimum_hourly', 35),
        };
    }

    /**
     * Calcula taxa horária para plano mensal.
     */
    protected function calculateMonthlyHourlyRate(array $mensalConfig): float
    {
        $monthlyPrice = $mensalConfig['price_per_month'] ?? 6000;
        $daysPerWeek = $mensalConfig['days_per_week'] ?? 5;
        $hoursPerDay = $mensalConfig['hours_per_day'] ?? 8;
        
        $weeksPerMonth = 4.33; // Média
        $hoursPerMonth = $daysPerWeek * $hoursPerDay * $weeksPerMonth;

        return $monthlyPrice / $hoursPerMonth;
    }

    /**
     * Verifica se há horas noturnas no período.
     */
    protected function hasNightHours(Carbon $start, ?Carbon $end): bool
    {
        if (!$end) {
            $end = $start->copy()->addHours(4); // Mínimo padrão
        }

        $nightStart = 22; // 22h
        $nightEnd = 6;    // 6h

        $startHour = $start->hour;
        $endHour = $end->hour;

        // Verifica se início ou fim está no período noturno
        if ($startHour >= $nightStart || $startHour < $nightEnd) {
            return true;
        }
        if ($endHour >= $nightStart || $endHour < $nightEnd) {
            return true;
        }

        // Verifica se o período atravessa a noite
        if ($start->diffInHours($end) > (24 - $nightStart + $nightEnd)) {
            return true;
        }

        return false;
    }

    /**
     * Calcula a margem de um preço.
     */
    public function calculateMargin(float $price, float $cost): array
    {
        $margin = $price - $cost;
        $marginPercent = $price > 0 ? ($margin / $price) * 100 : 0;
        $minMargin = config('financeiro.margin.minimum', 25);
        $targetMargin = config('financeiro.margin.target', 30);

        return [
            'price' => $price,
            'cost' => $cost,
            'margin' => $margin,
            'margin_percent' => round($marginPercent, 2),
            'meets_minimum' => $marginPercent >= $minMargin,
            'meets_target' => $marginPercent >= $targetMargin,
            'target_margin' => $targetMargin,
            'minimum_margin' => $minMargin,
        ];
    }

    /**
     * Calcula preço mínimo viável baseado no custo.
     */
    public function calculateMinimumViablePrice(float $caregiverCost): float
    {
        $targetMargin = config('financeiro.margin.target', 30) / 100;
        
        // Preço = Custo / (1 - Margem)
        $minPrice = $caregiverCost / (1 - $targetMargin);

        // Garante preço mínimo absoluto
        $absoluteMin = config('financeiro.pricing.minimum_hourly', 35);
        
        return max(round($minPrice, 2), $absoluteMin);
    }

    /**
     * Verifica se data é feriado (simplificado).
     */
    public function isHoliday(Carbon $date): bool
    {
        // Lista básica de feriados nacionais
        $holidays = [
            '01-01', // Ano Novo
            '04-21', // Tiradentes
            '05-01', // Dia do Trabalho
            '09-07', // Independência
            '10-12', // Nossa Senhora Aparecida
            '11-02', // Finados
            '11-15', // Proclamação da República
            '12-25', // Natal
        ];

        return in_array($date->format('m-d'), $holidays);
    }

    /**
     * Limpa cache de planos de preço.
     */
    public function clearPriceCache(): void
    {
        // Em produção, usar tags de cache para invalidação seletiva
        Cache::flush();
    }
}
