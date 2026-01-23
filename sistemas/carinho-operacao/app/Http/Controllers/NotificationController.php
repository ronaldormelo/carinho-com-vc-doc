<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de notificacoes.
 */
class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Lista notificacoes.
     */
    public function index(Request $request): JsonResponse
    {
        $clientId = $request->query('client_id');
        $type = $request->query('type');
        $status = $request->query('status');

        $query = Notification::with('status')
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->when($type, fn($q) => $q->where('notif_type', $type))
            ->when($status, fn($q) => $q->where('status_id', $status))
            ->orderBy('id', 'desc');

        $notifications = $query->paginate(20);

        return $this->success($notifications);
    }

    /**
     * Exibe detalhes de uma notificacao.
     */
    public function show(int $id): JsonResponse
    {
        $notification = Notification::with(['schedule', 'status'])->find($id);

        if (!$notification) {
            return $this->notFound('Notificacao nao encontrada.');
        }

        return $this->success($notification);
    }

    /**
     * Obtem historico de notificacoes de um cliente.
     */
    public function clientHistory(int $clientId): JsonResponse
    {
        $history = $this->notificationService->getClientNotificationHistory($clientId);

        return $this->success($history);
    }

    /**
     * Obtem notificacoes pendentes.
     */
    public function pending(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 100);

        $notifications = $this->notificationService->getPendingNotifications((int) $limit);

        return $this->success($notifications);
    }

    /**
     * Reenvia notificacao que falhou.
     */
    public function retry(int $id): JsonResponse
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return $this->notFound('Notificacao nao encontrada.');
        }

        if (!$notification->isFailed()) {
            return $this->error('Apenas notificacoes com falha podem ser reenviadas.');
        }

        try {
            $success = $this->notificationService->retryFailedNotification($notification);

            if ($success) {
                return $this->success($notification->fresh(), 'Notificacao reenviada para fila.');
            }

            return $this->error('Nao foi possivel reenviar a notificacao.');
        } catch (\Throwable $e) {
            return $this->error('Erro ao reenviar notificacao: ' . $e->getMessage());
        }
    }

    /**
     * Obtem tipos de notificacao disponiveis.
     */
    public function types(): JsonResponse
    {
        $types = Notification::types();

        return $this->success($types);
    }

    /**
     * Obtem estatisticas de notificacoes.
     */
    public function stats(Request $request): JsonResponse
    {
        $clientId = $request->query('client_id');

        $query = Notification::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId));

        $total = $query->count();
        $sent = (clone $query)->sent()->count();
        $pending = (clone $query)->pending()->count();
        $failed = (clone $query)->where('status_id', 3)->count();

        $byType = Notification::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->selectRaw('notif_type, COUNT(*) as count')
            ->groupBy('notif_type')
            ->pluck('count', 'notif_type')
            ->toArray();

        return $this->success([
            'total' => $total,
            'sent' => $sent,
            'pending' => $pending,
            'failed' => $failed,
            'delivery_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 0,
            'by_type' => $byType,
        ]);
    }
}
