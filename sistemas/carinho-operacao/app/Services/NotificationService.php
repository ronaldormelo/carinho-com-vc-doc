<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Assignment;
use App\Models\Emergency;
use App\Models\DomainNotificationStatus;
use App\Jobs\SendNotification;
use App\Integrations\Crm\CrmClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de notificacoes.
 */
class NotificationService
{
    public function __construct(
        protected CrmClient $crmClient
    ) {}

    /**
     * Notifica inicio do servico.
     */
    public function notifyServiceStart(Schedule $schedule): Notification
    {
        return $this->createAndDispatchNotification(
            $schedule->client_id,
            $schedule->id,
            Notification::TYPE_SERVICE_START,
            [
                'message' => config('branding.messages.service_started'),
                'schedule_id' => $schedule->id,
                'caregiver_id' => $schedule->caregiver_id,
                'start_time' => $schedule->start_time,
            ]
        );
    }

    /**
     * Notifica fim do servico.
     */
    public function notifyServiceEnd(Schedule $schedule): Notification
    {
        return $this->createAndDispatchNotification(
            $schedule->client_id,
            $schedule->id,
            Notification::TYPE_SERVICE_END,
            [
                'message' => config('branding.messages.service_ended'),
                'schedule_id' => $schedule->id,
                'caregiver_id' => $schedule->caregiver_id,
                'end_time' => $schedule->end_time,
            ]
        );
    }

    /**
     * Notifica alocacao de cuidador.
     */
    public function notifyCaregiverAssigned(Assignment $assignment): Notification
    {
        $serviceRequest = $assignment->serviceRequest;

        return $this->createAndDispatchNotification(
            $serviceRequest->client_id,
            null,
            Notification::TYPE_CAREGIVER_ASSIGNED,
            [
                'message' => config('branding.messages.caregiver_assigned'),
                'assignment_id' => $assignment->id,
                'caregiver_id' => $assignment->caregiver_id,
                'service_request_id' => $serviceRequest->id,
            ]
        );
    }

    /**
     * Notifica substituicao de cuidador.
     */
    public function notifyCaregiverReplaced(Assignment $assignment, int $oldCaregiverId): Notification
    {
        $serviceRequest = $assignment->serviceRequest;

        return $this->createAndDispatchNotification(
            $serviceRequest->client_id,
            null,
            Notification::TYPE_CAREGIVER_REPLACED,
            [
                'message' => config('branding.messages.caregiver_replaced'),
                'assignment_id' => $assignment->id,
                'new_caregiver_id' => $assignment->caregiver_id,
                'old_caregiver_id' => $oldCaregiverId,
                'service_request_id' => $serviceRequest->id,
            ]
        );
    }

    /**
     * Envia lembrete de agendamento.
     */
    public function sendScheduleReminder(Schedule $schedule): Notification
    {
        return $this->createAndDispatchNotification(
            $schedule->client_id,
            $schedule->id,
            Notification::TYPE_SCHEDULE_REMINDER,
            [
                'message' => config('branding.messages.schedule_reminder'),
                'schedule_id' => $schedule->id,
                'shift_date' => $schedule->shift_date->format('d/m/Y'),
                'start_time' => $schedule->start_time,
            ]
        );
    }

    /**
     * Notifica cancelamento.
     */
    public function notifyCancellation(Schedule $schedule, string $reason): Notification
    {
        return $this->createAndDispatchNotification(
            $schedule->client_id,
            $schedule->id,
            Notification::TYPE_CANCELLATION,
            [
                'message' => 'Seu atendimento foi cancelado.',
                'schedule_id' => $schedule->id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Notifica emergencia.
     */
    public function notifyEmergency(Emergency $emergency): Notification
    {
        $serviceRequest = $emergency->serviceRequest;

        return $this->createAndDispatchNotification(
            $serviceRequest->client_id,
            null,
            Notification::TYPE_EMERGENCY_ALERT,
            [
                'message' => config('branding.messages.emergency_alert'),
                'emergency_id' => $emergency->id,
                'severity' => $emergency->severity?->code,
                'description' => $emergency->description,
            ]
        );
    }

    /**
     * Cria e despacha notificacao.
     */
    protected function createAndDispatchNotification(
        int $clientId,
        ?int $scheduleId,
        string $type,
        array $data
    ): Notification {
        $notification = Notification::create([
            'client_id' => $clientId,
            'schedule_id' => $scheduleId,
            'notif_type' => $type,
            'status_id' => DomainNotificationStatus::QUEUED,
        ]);

        // Despacha job para envio assincrono
        SendNotification::dispatch($notification, $data);

        Log::info('Notificacao criada', [
            'notification_id' => $notification->id,
            'type' => $type,
            'client_id' => $clientId,
        ]);

        return $notification;
    }

    /**
     * Processa envio de notificacao.
     */
    public function processNotification(Notification $notification, array $data): bool
    {
        try {
            // Busca dados do cliente
            $clientData = $this->fetchClientData($notification->client_id);

            if (!$clientData) {
                Log::warning('Cliente nao encontrado para notificacao', [
                    'notification_id' => $notification->id,
                    'client_id' => $notification->client_id,
                ]);
                $notification->markAsFailed();
                return false;
            }

            // Determina canal de envio
            $channel = $this->determineChannel($clientData, $notification->notif_type);

            // Envia pelo canal apropriado
            $sent = match ($channel) {
                'whatsapp' => $this->sendViaWhatsApp($clientData, $data),
                'email' => $this->sendViaEmail($clientData, $data),
                'push' => $this->sendViaPush($clientData, $data),
                default => false,
            };

            if ($sent) {
                $notification->markAsSent();
                return true;
            }

            $notification->markAsFailed();
            return false;

        } catch (\Throwable $e) {
            Log::error('Erro ao processar notificacao', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            $notification->markAsFailed();
            return false;
        }
    }

    /**
     * Busca dados do cliente via CRM.
     */
    protected function fetchClientData(int $clientId): ?array
    {
        $response = $this->crmClient->getClient($clientId);

        if (!$response['ok']) {
            return null;
        }

        return $response['body']['client'] ?? null;
    }

    /**
     * Determina melhor canal para notificacao.
     */
    protected function determineChannel(array $clientData, string $notificationType): string
    {
        $channels = config('operacao.notifications.channels', ['whatsapp', 'email']);
        $preferred = config('operacao.notifications.preferred_channel', 'whatsapp');

        // Usa canal preferido se disponivel
        if (in_array($preferred, $channels)) {
            $hasContact = match ($preferred) {
                'whatsapp' => !empty($clientData['phone']),
                'email' => !empty($clientData['email']),
                'push' => !empty($clientData['push_token']),
                default => false,
            };

            if ($hasContact) {
                return $preferred;
            }
        }

        // Fallback para outros canais
        foreach ($channels as $channel) {
            $hasContact = match ($channel) {
                'whatsapp' => !empty($clientData['phone']),
                'email' => !empty($clientData['email']),
                'push' => !empty($clientData['push_token']),
                default => false,
            };

            if ($hasContact) {
                return $channel;
            }
        }

        return 'email'; // Fallback padrao
    }

    /**
     * Envia via WhatsApp.
     */
    protected function sendViaWhatsApp(array $clientData, array $data): bool
    {
        // Delegado para o WhatsApp service/job
        $phone = $clientData['phone'] ?? null;
        if (!$phone) {
            return false;
        }

        // Aqui seria chamado o job de envio de WhatsApp
        // SendWhatsAppNotification::dispatch($phone, $data);

        return true;
    }

    /**
     * Envia via Email.
     */
    protected function sendViaEmail(array $clientData, array $data): bool
    {
        $email = $clientData['email'] ?? null;
        if (!$email) {
            return false;
        }

        // Aqui seria chamado o job de envio de email
        // SendEmailNotification::dispatch($email, $data);

        return true;
    }

    /**
     * Envia via Push.
     */
    protected function sendViaPush(array $clientData, array $data): bool
    {
        $pushToken = $clientData['push_token'] ?? null;
        if (!$pushToken) {
            return false;
        }

        // Aqui seria chamado o job de envio de push
        // SendPushNotification::dispatch($pushToken, $data);

        return true;
    }

    /**
     * Obtem notificacoes pendentes.
     */
    public function getPendingNotifications(int $limit = 100): Collection
    {
        return Notification::pending()
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtem historico de notificacoes de um cliente.
     */
    public function getClientNotificationHistory(int $clientId, int $limit = 50): Collection
    {
        return Notification::forClient($clientId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->with('status')
            ->get();
    }

    /**
     * Reenvia notificacao que falhou.
     */
    public function retryFailedNotification(Notification $notification): bool
    {
        if (!$notification->isFailed()) {
            return false;
        }

        $notification->status_id = DomainNotificationStatus::QUEUED;
        $notification->save();

        // Re-dispatch job
        SendNotification::dispatch($notification, []);

        return true;
    }
}
