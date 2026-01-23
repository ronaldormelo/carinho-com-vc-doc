<?php

namespace App\Jobs;

use App\Models\Caregiver;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCaregiverNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(
        private Caregiver $caregiver,
        private string $type,
        private array $data = []
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notificationService): void
    {
        Log::info('Processando notificacao para cuidador', [
            'caregiver_id' => $this->caregiver->id,
            'type' => $this->type,
        ]);

        $result = $notificationService->send($this->caregiver, $this->type, $this->data);

        if (!$result['success']) {
            Log::warning('Falha ao enviar notificacao', [
                'caregiver_id' => $this->caregiver->id,
                'type' => $this->type,
                'results' => $result['results'] ?? [],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job de notificacao falhou', [
            'caregiver_id' => $this->caregiver->id,
            'type' => $this->type,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'notification',
            'caregiver:' . $this->caregiver->id,
            'type:' . $this->type,
        ];
    }
}
