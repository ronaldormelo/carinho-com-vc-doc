<?php

namespace App\Jobs;

use App\Services\Email\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para envio de emails.
 *
 * Processa envio assincrono de emails com retry.
 */
class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 120];

    /**
     * Tipo de email.
     */
    private string $type;

    /**
     * Dados do email.
     */
    private array $data;

    /**
     * Cria nova instancia do job.
     */
    public function __construct(string $type, array $data)
    {
        $this->type = $type;
        $this->data = $data;
        $this->onQueue('notifications');
    }

    /**
     * Executa o job.
     */
    public function handle(EmailService $emailService): void
    {
        Log::info('Sending email', [
            'type' => $this->type,
            'email' => $this->data['email'] ?? 'unknown',
        ]);

        $success = match ($this->type) {
            'welcome' => $emailService->sendWelcomeEmail($this->data),
            'signup_confirmation' => $emailService->sendSignupConfirmation($this->data),
            'contract' => $emailService->sendContractEmail($this->data),
            'service_scheduled' => $emailService->sendServiceScheduledEmail($this->data),
            'feedback_request' => $emailService->sendFeedbackRequestEmail($this->data),
            'payment_notification' => $emailService->sendPaymentNotificationEmail($this->data),
            'payout_processed' => $emailService->sendPayoutProcessedEmail($this->data),
            'generic' => $emailService->sendGenericEmail(
                $this->data['email'],
                $this->data['name'],
                $this->data['subject'],
                $this->data['template'],
                $this->data['data']
            ),
            default => throw new \InvalidArgumentException("Unknown email type: {$this->type}"),
        };

        if (!$success) {
            throw new \Exception("Failed to send email of type: {$this->type}");
        }

        Log::info('Email sent successfully', [
            'type' => $this->type,
            'email' => $this->data['email'] ?? 'unknown',
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email sending failed permanently', [
            'type' => $this->type,
            'email' => $this->data['email'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Tags para monitoramento.
     */
    public function tags(): array
    {
        return [
            'email',
            'type:' . $this->type,
        ];
    }
}
