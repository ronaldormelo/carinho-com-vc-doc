<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Carbon\Carbon;

class MessageAutomationService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup
    ) {
    }

    public function handleInboundAutoReplies(int $conversationId, string $phone, bool $isNewConversation): void
    {
        if ($this->isOutsideWorkingHours()) {
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

    private function isOutsideWorkingHours(): bool
    {
        $timezone = config('atendimento.timezone', 'America/Sao_Paulo');
        $start = config('atendimento.working_hours.start', '08:00');
        $end = config('atendimento.working_hours.end', '18:00');

        $now = Carbon::now($timezone);
        $startAt = Carbon::parse($now->toDateString() . ' ' . $start, $timezone);
        $endAt = Carbon::parse($now->toDateString() . ' ' . $end, $timezone);

        return $now->lt($startAt) || $now->gt($endAt);
    }
}
