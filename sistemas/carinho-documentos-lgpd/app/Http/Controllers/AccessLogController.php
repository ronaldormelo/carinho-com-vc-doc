<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessLogController extends Controller
{
    /**
     * Lista logs de acesso.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AccessLog::with(['document', 'action']);

        if ($request->has('document_id')) {
            $query->where('document_id', $request->input('document_id'));
        }

        if ($request->has('actor_id')) {
            $query->where('actor_id', $request->input('actor_id'));
        }

        if ($request->has('action_id')) {
            $query->where('action_id', $request->input('action_id'));
        }

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return $this->success($logs);
    }

    /**
     * Logs por documento.
     */
    public function byDocument(int $documentId): JsonResponse
    {
        $logs = AccessLog::findByDocument($documentId)
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action->code,
                'actor_id' => $log->actor_id,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ])
            ->toArray();

        return $this->success($logs);
    }

    /**
     * Logs por ator.
     */
    public function byActor(int $actorId): JsonResponse
    {
        $logs = AccessLog::findByActor($actorId)
            ->map(fn ($log) => [
                'id' => $log->id,
                'document_id' => $log->document_id,
                'action' => $log->action->code,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ])
            ->toArray();

        return $this->success($logs);
    }

    /**
     * Relatorio de acessos.
     */
    public function report(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $byAction = AccessLog::select('action_id', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('action_id')
            ->with('action')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->action->code => $item->count])
            ->toArray();

        $byDay = AccessLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $totalLogs = AccessLog::whereBetween('created_at', [$startDate, $endDate])->count();
        $uniqueDocuments = AccessLog::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('document_id')
            ->count();
        $uniqueActors = AccessLog::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('actor_id')
            ->count();

        return $this->success([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'total_logs' => $totalLogs,
                'unique_documents' => $uniqueDocuments,
                'unique_actors' => $uniqueActors,
            ],
            'by_action' => $byAction,
            'by_day' => $byDay,
        ]);
    }
}
