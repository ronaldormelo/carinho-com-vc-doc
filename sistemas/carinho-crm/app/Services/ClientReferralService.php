<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Lead;
use App\Models\ClientReferral;
use App\Models\ClientEvent;
use App\Models\Domain\DomainEventType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * Serviço de Indicações de Clientes (Programa de Referral)
 * 
 * Prática tradicional de aquisição de clientes por indicação:
 * - Rastreia quem indicou quem
 * - Acompanha conversão de indicações
 * - Fornece dados para programas de benefícios
 */
class ClientReferralService
{
    /**
     * Registra uma nova indicação
     */
    public function createReferral(int $referrerClientId, array $data): ClientReferral
    {
        return DB::transaction(function () use ($referrerClientId, $data) {
            $referral = ClientReferral::create([
                'referrer_client_id' => $referrerClientId,
                'referred_name' => $data['referred_name'],
                'referred_phone' => $data['referred_phone'] ?? null,
                'status' => ClientReferral::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            // Registra evento na timeline do cliente que indicou
            $referrer = Client::find($referrerClientId);
            if ($referrer) {
                ClientEvent::logReferralMade($referrer, $referral);
            }

            Log::channel('audit')->info('Indicação registrada', [
                'referrer_client_id' => $referrerClientId,
                'referral_id' => $referral->id,
                'referred_name' => $data['referred_name'],
            ]);

            return $referral;
        });
    }

    /**
     * Vincula indicação a um lead
     */
    public function linkToLead(ClientReferral $referral, Lead $lead): ClientReferral
    {
        return DB::transaction(function () use ($referral, $lead) {
            $referral->referred_lead_id = $lead->id;
            $referral->status = ClientReferral::STATUS_CONTACTED;
            $referral->save();

            // Atualiza fonte do lead
            $lead->update(['source' => 'referral']);

            Log::channel('audit')->info('Indicação vinculada a lead', [
                'referral_id' => $referral->id,
                'lead_id' => $lead->id,
            ]);

            return $referral;
        });
    }

    /**
     * Marca indicação como convertida
     */
    public function markAsConverted(ClientReferral $referral, Client $client): ClientReferral
    {
        return DB::transaction(function () use ($referral, $client) {
            $referral->markAsConverted($client->id);

            // Atualiza cliente para indicar que foi indicado
            $client->update([
                'referred_by_client_id' => $referral->referrer_client_id,
                'referral_source' => 'client_referral',
            ]);

            Log::channel('audit')->info('Indicação convertida em cliente', [
                'referral_id' => $referral->id,
                'client_id' => $client->id,
                'referrer_client_id' => $referral->referrer_client_id,
            ]);

            return $referral;
        });
    }

    /**
     * Marca indicação como perdida
     */
    public function markAsLost(ClientReferral $referral, ?string $reason = null): ClientReferral
    {
        $referral->markAsLost($reason);

        Log::channel('audit')->info('Indicação marcada como perdida', [
            'referral_id' => $referral->id,
            'reason' => $reason,
        ]);

        return $referral;
    }

    /**
     * Obtém indicações pendentes de contato
     */
    public function getPendingReferrals(): Collection
    {
        return ClientReferral::pending()
            ->with(['referrer'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Obtém indicações em andamento (contatadas mas não convertidas)
     */
    public function getInProgressReferrals(): Collection
    {
        return ClientReferral::contacted()
            ->with(['referrer', 'referredLead'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Obtém top indicadores (clientes que mais indicam)
     */
    public function getTopReferrers(int $limit = 10): Collection
    {
        return Client::withCount(['referrals', 'referrals as converted_referrals_count' => function ($q) {
            $q->where('status', ClientReferral::STATUS_CONVERTED);
        }])
        ->having('referrals_count', '>', 0)
        ->orderBy('converted_referrals_count', 'desc')
        ->orderBy('referrals_count', 'desc')
        ->limit($limit)
        ->get();
    }

    /**
     * Obtém estatísticas gerais de indicações
     */
    public function getStatistics($startDate = null, $endDate = null): array
    {
        $query = ClientReferral::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $referrals = $query->get();
        $total = $referrals->count();

        $converted = $referrals->where('status', ClientReferral::STATUS_CONVERTED)->count();
        $pending = $referrals->where('status', ClientReferral::STATUS_PENDING)->count();
        $contacted = $referrals->where('status', ClientReferral::STATUS_CONTACTED)->count();
        $lost = $referrals->where('status', ClientReferral::STATUS_LOST)->count();

        // Tempo médio de conversão
        $convertedWithDates = $referrals->where('status', ClientReferral::STATUS_CONVERTED)
            ->filter(fn($r) => $r->converted_at !== null);
        
        $avgConversionDays = $convertedWithDates->isEmpty() 
            ? 0 
            : $convertedWithDates->avg(fn($r) => $r->created_at->diffInDays($r->converted_at));

        return [
            'total' => $total,
            'pending' => $pending,
            'contacted' => $contacted,
            'converted' => $converted,
            'lost' => $lost,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0,
            'avg_conversion_days' => round($avgConversionDays, 1),
            'unique_referrers' => $referrals->pluck('referrer_client_id')->unique()->count(),
        ];
    }

    /**
     * Obtém histórico de indicações de um cliente
     */
    public function getClientReferralHistory(int $clientId): array
    {
        $made = ClientReferral::fromClient($clientId)
            ->with(['referredLead', 'referredClient'])
            ->orderBy('created_at', 'desc')
            ->get();

        $received = ClientReferral::where('referred_client_id', $clientId)
            ->with('referrer')
            ->first();

        return [
            'made' => $made,
            'received' => $received,
            'stats' => ClientReferral::getReferrerStats($clientId),
        ];
    }

    /**
     * Verifica se lead foi indicado
     */
    public function getLeadReferral(int $leadId): ?ClientReferral
    {
        return ClientReferral::where('referred_lead_id', $leadId)->first();
    }

    /**
     * Busca indicações antigas sem contato (para follow-up)
     */
    public function getStaleReferrals(int $daysWithoutContact = 7): Collection
    {
        return ClientReferral::pending()
            ->where('created_at', '<', now()->subDays($daysWithoutContact))
            ->with('referrer')
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
