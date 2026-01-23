<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;

/**
 * Servico de automacao de mensagens.
 *
 * Gerencia envio automatico de mensagens baseado em gatilhos:
 * - Primeira resposta para novos contatos
 * - Mensagem fora do horario comercial
 * - Solicitacao de feedback apos encerramento
 * - Lembretes de acompanhamento
 */
class MessageAutomationService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private WorkingHoursService $workingHoursService
    ) {
    }

    public function handleInboundAutoReplies(int $conversationId, string $phone, bool $isNewConversation): void
    {
        if ($this->workingHoursService->isOutsideWorkingHours()) {
            $this->queueAutoMessage('after_hours', $conversationId, $phone);
            return;
        }

        if ($isNewConversation) {
            $this->queueAutoMessage('first_response', $conversationId, $phone);
        }
    }

    public function sendFeedbackRequest(int $conversationId, string $phone): void
    {
        $this->queueAutoMessage('feedback_request', $conversationId, $phone);
    }

    /**
     * Envia lembrete de acompanhamento.
     */
    public function sendFollowUpReminder(int $conversationId, string $phone): void
    {
        $this->queueAutoMessage('follow_up', $conversationId, $phone);
    }

    /**
     * Envia mensagem customizada com template.
     */
    public function sendTemplateMessage(int $conversationId, string $phone, string $templateKey): bool
    {
        $template = $this->repository->findAutoRuleTemplate($templateKey);

        if (!$template) {
            return false;
        }

        $messageId = $this->repository->createMessage([
            'conversation_id' => $conversationId,
            'direction_id' => $this->domainLookup->messageDirectionId('outbound'),
            'body' => $template->body,
            'media_url' => null,
            'sent_at' => null,
            'status_id' => $this->domainLookup->messageStatusId('queued'),
        ]);

        SendWhatsAppMessageJob::dispatch($conversationId, $messageId, $phone, $template->body, null);

        return true;
    }

    /**
     * Verifica se esta dentro do horario comercial.
     */
    public function isWithinWorkingHours(): bool
    {
        return $this->workingHoursService->isWithinWorkingHours();
    }

    private function queueAutoMessage(string $triggerKey, int $conversationId, string $phone): void
    {
        $template = $this->repository->findAutoRuleTemplate($triggerKey);

        if (!$template) {
            return;
        }

        $messageId = $this->repository->createMessage([
            'conversation_id' => $conversationId,
            'direction_id' => $this->domainLookup->messageDirectionId('outbound'),
            'body' => $template->body,
            'media_url' => null,
            'sent_at' => null,
            'status_id' => $this->domainLookup->messageStatusId('queued'),
        ]);

        SendWhatsAppMessageJob::dispatch($conversationId, $messageId, $phone, $template->body, null);
    }
}
