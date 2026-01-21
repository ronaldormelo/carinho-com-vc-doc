<?php

namespace App\Services;

use App\Models\Interaction;
use App\Models\Domain\DomainInteractionChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InteractionService
{
    /**
     * Cria uma nova interação
     */
    public function createInteraction(array $data): Interaction
    {
        return DB::transaction(function () use ($data) {
            // Data da interação padrão é agora
            $data['occurred_at'] = $data['occurred_at'] ?? now();

            $interaction = Interaction::create($data);

            Log::channel('audit')->info('Interação registrada', [
                'interaction_id' => $interaction->id,
                'lead_id' => $data['lead_id'],
                'channel_id' => $data['channel_id'],
            ]);

            return $interaction;
        });
    }

    /**
     * Registra interação via WhatsApp
     */
    public function createWhatsAppInteraction(int $leadId, string $summary): Interaction
    {
        return $this->createInteraction([
            'lead_id' => $leadId,
            'channel_id' => DomainInteractionChannel::WHATSAPP,
            'summary' => $summary,
        ]);
    }

    /**
     * Registra interação via e-mail
     */
    public function createEmailInteraction(int $leadId, string $summary): Interaction
    {
        return $this->createInteraction([
            'lead_id' => $leadId,
            'channel_id' => DomainInteractionChannel::EMAIL,
            'summary' => $summary,
        ]);
    }

    /**
     * Registra interação via telefone
     */
    public function createPhoneInteraction(int $leadId, string $summary): Interaction
    {
        return $this->createInteraction([
            'lead_id' => $leadId,
            'channel_id' => DomainInteractionChannel::PHONE,
            'summary' => $summary,
        ]);
    }

    /**
     * Obtém estatísticas de interações
     */
    public function getStatistics($startDate, $endDate): array
    {
        $query = Interaction::whereBetween('occurred_at', [$startDate, $endDate]);

        $total = (clone $query)->count();
        
        $byChannel = (clone $query)
            ->selectRaw('channel_id, COUNT(*) as count')
            ->groupBy('channel_id')
            ->pluck('count', 'channel_id')
            ->toArray();

        $byDay = (clone $query)
            ->selectRaw('DATE(occurred_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Média de interações por lead
        $avgPerLead = (clone $query)
            ->selectRaw('COUNT(*) / COUNT(DISTINCT lead_id) as avg')
            ->value('avg');

        // Leads sem interação no período
        $leadsWithInteraction = (clone $query)
            ->distinct('lead_id')
            ->count('lead_id');

        return [
            'total' => $total,
            'by_channel' => $byChannel,
            'by_day' => $byDay,
            'avg_per_lead' => round($avgPerLead ?? 0, 2),
            'leads_with_interaction' => $leadsWithInteraction,
        ];
    }

    /**
     * Calcula tempo médio de primeira resposta
     */
    public function getAverageFirstResponseTime($startDate, $endDate): ?float
    {
        // Simplificação: tempo entre criação do lead e primeira interação
        $result = DB::select("
            SELECT AVG(TIMESTAMPDIFF(HOUR, l.created_at, i.first_interaction)) as avg_hours
            FROM leads l
            INNER JOIN (
                SELECT lead_id, MIN(occurred_at) as first_interaction
                FROM interactions
                WHERE occurred_at BETWEEN ? AND ?
                GROUP BY lead_id
            ) i ON l.id = i.lead_id
            WHERE l.created_at BETWEEN ? AND ?
        ", [$startDate, $endDate, $startDate, $endDate]);

        return $result[0]->avg_hours ?? null;
    }
}
