<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Models\UtmCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller para gerenciamento de leads.
 */
class LeadController extends Controller
{
    /**
     * Lista submissoes de formulario.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FormSubmission::with(['form', 'utm', 'urgency', 'serviceType']);

        // Filtros
        if ($request->has('synced')) {
            $synced = filter_var($request->input('synced'), FILTER_VALIDATE_BOOLEAN);
            $query->where('synced_to_crm', $synced);
        }

        if ($request->has('form_id')) {
            $query->where('form_id', $request->input('form_id'));
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        $submissions = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($submissions);
    }

    /**
     * Detalhes de uma submissao.
     */
    public function show(int $id): JsonResponse
    {
        $submission = FormSubmission::with(['form', 'utm', 'urgency', 'serviceType'])
            ->findOrFail($id);

        return response()->json($submission);
    }

    /**
     * Marca submissao como sincronizada.
     */
    public function markSynced(int $id): JsonResponse
    {
        $submission = FormSubmission::findOrFail($id);
        $submission->markAsSynced();

        return response()->json([
            'status' => 'ok',
            'message' => 'Submissao marcada como sincronizada',
        ]);
    }

    /**
     * Estatisticas de leads.
     */
    public function stats(Request $request): JsonResponse
    {
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $total = FormSubmission::whereBetween('created_at', [$fromDate, $toDate])->count();
        $synced = FormSubmission::whereBetween('created_at', [$fromDate, $toDate])
            ->where('synced_to_crm', true)
            ->count();

        $byUrgency = FormSubmission::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('urgency_id, COUNT(*) as count')
            ->groupBy('urgency_id')
            ->get()
            ->pluck('count', 'urgency_id');

        $byServiceType = FormSubmission::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('service_type_id, COUNT(*) as count')
            ->groupBy('service_type_id')
            ->get()
            ->pluck('count', 'service_type_id');

        $bySource = UtmCampaign::join('form_submissions', 'utm_campaigns.id', '=', 'form_submissions.utm_id')
            ->whereBetween('form_submissions.created_at', [$fromDate, $toDate])
            ->selectRaw('utm_campaigns.source, COUNT(*) as count')
            ->groupBy('utm_campaigns.source')
            ->get()
            ->pluck('count', 'source');

        return response()->json([
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'total' => $total,
            'synced' => $synced,
            'pending_sync' => $total - $synced,
            'by_urgency' => $byUrgency,
            'by_service_type' => $byServiceType,
            'by_source' => $bySource,
        ]);
    }
}
