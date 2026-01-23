<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignApproval;
use App\Models\CampaignAuditLog;
use App\Models\Domain\DomainApprovalStatus;
use App\Models\Domain\DomainCampaignStatus;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de aprovação de orçamento de campanhas.
 * 
 * Controla o fluxo de aprovação para campanhas com
 * orçamento acima do limite configurado.
 */
class CampaignApprovalService
{
    /**
     * Limite de orçamento para aprovação automática (R$).
     */
    private const AUTO_APPROVAL_LIMIT = 500.00;

    /**
     * Verifica se campanha requer aprovação.
     */
    public function requiresApproval(float $budget): bool
    {
        return $budget > self::AUTO_APPROVAL_LIMIT;
    }

    /**
     * Solicita aprovação para campanha.
     */
    public function requestApproval(
        int $campaignId,
        float $budget,
        int $requestedBy,
        ?string $justification = null
    ): CampaignApproval {
        // Verifica se já existe solicitação pendente
        $existing = CampaignApproval::where('campaign_id', $campaignId)
            ->pending()
            ->first();

        if ($existing) {
            throw new \Exception('Já existe uma solicitação de aprovação pendente para esta campanha.');
        }

        $approval = CampaignApproval::createRequest(
            $campaignId,
            $budget,
            $requestedBy,
            $justification
        );

        // Atualiza flag na campanha
        Campaign::where('id', $campaignId)->update(['approval_required' => true]);

        Log::info('Campaign approval requested', [
            'campaign_id' => $campaignId,
            'budget' => $budget,
            'requested_by' => $requestedBy,
        ]);

        return $approval;
    }

    /**
     * Aprova solicitação de orçamento.
     */
    public function approve(
        int $approvalId,
        int $approvedBy,
        ?string $notes = null,
        ?array $requestInfo = null
    ): CampaignApproval {
        $approval = CampaignApproval::findOrFail($approvalId);

        if (!$approval->isPending()) {
            throw new \Exception('Esta solicitação já foi processada.');
        }

        $approval->approve($approvedBy, $notes);

        // Atualiza campanha
        Campaign::where('id', $approval->campaign_id)->update([
            'approval_required' => false,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        // Registra no log de auditoria
        CampaignAuditLog::logStatusChange(
            $approval->campaign_id,
            CampaignAuditLog::ACTION_APPROVED,
            $approvedBy,
            $requestInfo
        );

        Log::info('Campaign approval approved', [
            'approval_id' => $approvalId,
            'campaign_id' => $approval->campaign_id,
            'approved_by' => $approvedBy,
        ]);

        return $approval;
    }

    /**
     * Rejeita solicitação de orçamento.
     */
    public function reject(
        int $approvalId,
        int $rejectedBy,
        ?string $notes = null,
        ?array $requestInfo = null
    ): CampaignApproval {
        $approval = CampaignApproval::findOrFail($approvalId);

        if (!$approval->isPending()) {
            throw new \Exception('Esta solicitação já foi processada.');
        }

        $approval->reject($rejectedBy, $notes);

        // Registra no log de auditoria
        CampaignAuditLog::logStatusChange(
            $approval->campaign_id,
            CampaignAuditLog::ACTION_REJECTED,
            $rejectedBy,
            $requestInfo
        );

        Log::info('Campaign approval rejected', [
            'approval_id' => $approvalId,
            'campaign_id' => $approval->campaign_id,
            'rejected_by' => $rejectedBy,
        ]);

        return $approval;
    }

    /**
     * Lista solicitações pendentes.
     */
    public function listPending(): array
    {
        return CampaignApproval::with(['campaign.channel', 'status'])
            ->pending()
            ->orderBy('requested_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Verifica se campanha pode ser ativada (aprovação OK).
     */
    public function canActivate(int $campaignId): bool
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return false;
        }

        // Se não requer aprovação, pode ativar
        if (!$this->requiresApproval($campaign->budget)) {
            return true;
        }

        // Verifica se tem aprovação válida
        return CampaignApproval::where('campaign_id', $campaignId)
            ->approved()
            ->where('requested_budget', '>=', $campaign->budget)
            ->exists();
    }

    /**
     * Obtém histórico de aprovações da campanha.
     */
    public function getHistory(int $campaignId): array
    {
        return CampaignApproval::with('status')
            ->where('campaign_id', $campaignId)
            ->orderBy('requested_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Obtém limite de aprovação automática.
     */
    public function getAutoApprovalLimit(): float
    {
        return self::AUTO_APPROVAL_LIMIT;
    }
}
