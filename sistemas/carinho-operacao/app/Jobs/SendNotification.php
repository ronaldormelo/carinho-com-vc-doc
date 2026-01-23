<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para envio de notificacoes de forma assincrona.
 */
class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas.
     */
    public int $tries = 3;

    /**
     * Tempo de backoff entre tentativas (segundos).
     */
    public array $backoff = [10, 30, 60];

    /**
     * Cria nova instancia do job.
     */
    public function __construct(
        public Notification $notification,
        public array $data = []
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Executa o job.
     */
    public function handle(NotificationService $notificationService): void
    {
        Log::info('Processando envio de notificacao', [
            'notification_id' => $this->notification->id,
            'type' => $this->notification->notif_type,
        ]);

        $success = $notificationService->processNotification($this->notification, $this->data);

        if ($success) {
            Log::info('Notificacao enviada com sucesso', [
                'notification_id' => $this->notification->id,
            ]);
        } else {
            Log::warning('Falha no envio da notificacao', [
                'notification_id' => $this->notification->id,
            ]);
        }
    }

    /**
     * Trata falha do job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de notificacao falhou', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage(),
        ]);

        $this->notification->markAsFailed();
    }
}
