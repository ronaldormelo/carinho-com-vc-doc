<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\Domain\DomainDealStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DealService
{
    /**
     * Cria um novo deal
     */
    public function createDeal(array $data): Deal
    {
        return DB::transaction(function () use ($data) {
            // Se não especificado, pega o primeiro estágio do pipeline
            if (!isset($data['stage_id'])) {
                $firstStage = PipelineStage::active()->ordered()->first();
                $data['stage_id'] = $firstStage?->id ?? 1;
            }

            // Status inicial é "open"
            $data['status_id'] = $data['status_id'] ?? DomainDealStatus::OPEN;

            $deal = Deal::create($data);

            Log::channel('audit')->info('Deal criado', [
                'deal_id' => $deal->id,
                'lead_id' => $data['lead_id'],
                'stage_id' => $data['stage_id'],
            ]);

            return $deal;
        });
    }

    /**
     * Atualiza um deal existente
     */
    public function updateDeal(Deal $deal, array $data): Deal
    {
        return DB::transaction(function () use ($deal, $data) {
            $deal->update($data);

            Log::channel('audit')->info('Deal atualizado', [
                'deal_id' => $deal->id,
                'changes' => $deal->getChanges(),
            ]);

            return $deal->fresh();
        });
    }

    /**
     * Move deal para um estágio específico
     */
    public function moveToStage(Deal $deal, int $stageId): Deal
    {
        if (!$deal->canMoveToStage($stageId)) {
            throw new \InvalidArgumentException('Não é possível mover para este estágio');
        }

        return DB::transaction(function () use ($deal, $stageId) {
            $oldStageId = $deal->stage_id;
            $deal->stage_id = $stageId;
            $deal->save();

            Log::channel('audit')->info('Deal movido de estágio', [
                'deal_id' => $deal->id,
                'from_stage' => $oldStageId,
                'to_stage' => $stageId,
            ]);

            return $deal->fresh();
        });
    }

    /**
     * Marca deal como ganho
     */
    public function markAsWon(Deal $deal): Deal
    {
        if (!$deal->isOpen()) {
            throw new \InvalidArgumentException('Deal não está aberto');
        }

        return DB::transaction(function () use ($deal) {
            $deal->status_id = DomainDealStatus::WON;
            $deal->save();

            Log::channel('audit')->info('Deal ganho', [
                'deal_id' => $deal->id,
                'value' => $deal->value_estimated,
            ]);

            return $deal->fresh();
        });
    }

    /**
     * Marca deal como perdido
     */
    public function markAsLost(Deal $deal): Deal
    {
        if (!$deal->isOpen()) {
            throw new \InvalidArgumentException('Deal não está aberto');
        }

        return DB::transaction(function () use ($deal) {
            $deal->status_id = DomainDealStatus::LOST;
            $deal->save();

            Log::channel('audit')->info('Deal perdido', [
                'deal_id' => $deal->id,
            ]);

            return $deal->fresh();
        });
    }

    /**
     * Cria proposta para o deal
     */
    public function createProposal(Deal $deal, array $data): \App\Models\Proposal
    {
        return DB::transaction(function () use ($deal, $data) {
            $data['deal_id'] = $deal->id;
            
            $proposal = $deal->proposals()->create($data);

            // Atualiza valor estimado do deal
            if ($data['price'] ?? null) {
                $deal->value_estimated = $data['price'];
                $deal->save();
            }

            Log::channel('audit')->info('Proposta criada', [
                'deal_id' => $deal->id,
                'proposal_id' => $proposal->id,
                'price' => $data['price'] ?? 0,
            ]);

            return $proposal;
        });
    }

    /**
     * Obtém estatísticas de deals
     */
    public function getStatistics($startDate = null, $endDate = null): array
    {
        $query = Deal::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $total = $query->count();
        $won = (clone $query)->won()->count();
        $lost = (clone $query)->lost()->count();
        $open = (clone $query)->open()->count();

        $totalValue = (clone $query)->won()->sum('value_estimated');
        $avgValue = (clone $query)->won()->avg('value_estimated');

        $winRate = ($won + $lost) > 0 ? round(($won / ($won + $lost)) * 100, 2) : 0;

        return [
            'total' => $total,
            'open' => $open,
            'won' => $won,
            'lost' => $lost,
            'win_rate' => $winRate,
            'total_value' => round($totalValue, 2),
            'avg_value' => round($avgValue ?? 0, 2),
        ];
    }
}
