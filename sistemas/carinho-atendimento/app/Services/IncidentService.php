<?php

namespace App\Services;

use App\Jobs\NotifyOperacaoJob;
use App\Jobs\SyncCrmJob;
use App\Repositories\AtendimentoRepository;
use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

/**
 * Servico de gestao de incidentes e reclamacoes.
 *
 * Categorias de incidentes:
 * - complaint: Reclamacao formal
 * - delay: Atraso no atendimento
 * - quality: Problema de qualidade
 * - communication: Falha de comunicacao
 * - billing: Problema de cobranca
 * - caregiver: Problema com cuidador
 * - emergency: Emergencia
 * - suggestion: Sugestao
 * - other: Outros
 */
class IncidentService
{
    public function __construct(
        private AtendimentoRepository $repository,
        private DomainLookup $domainLookup,
        private ConversationHistoryService $historyService
    ) {
    }

    /**
     * Cria um novo incidente.
     */
    public function createIncident(
        int $conversationId,
        string $severityCode,
        ?string $categoryCode = null,
        ?string $notes = null,
        ?int $agentId = null
    ): int {
        $now = now()->toDateTimeString();
        $severityId = $this->domainLookup->incidentSeverityId($severityCode);
        $categoryId = $categoryCode
            ? $this->domainLookup->incidentCategoryId($categoryCode)
            : $this->domainLookup->incidentCategoryId('other');

        $incidentId = $this->repository->createIncident([
            'conversation_id' => $conversationId,
            'severity_id' => $severityId,
            'category_id' => $categoryId,
            'notes' => $notes,
            'resolution' => null,
            'resolved_at' => null,
            'resolved_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Registra no historico da conversa
        $this->historyService->record(
            $conversationId,
            'incident',
            $agentId,
            null,
            "{$severityCode}:{$categoryCode}",
            $notes
        );

        $conversation = $this->repository->findConversationById($conversationId);
        $contact = $conversation ? $this->repository->findContactById($conversation->contact_id) : null;

        $payload = [
            'incident_id' => $incidentId,
            'conversation_id' => $conversationId,
            'severity' => $severityCode,
            'category' => $categoryCode ?? 'other',
            'notes' => $notes,
            'contact' => $contact ? [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'city' => $contact->city,
            ] : null,
        ];

        // Notifica operacao para severidades altas
        if (in_array($severityCode, ['high', 'critical'], true)) {
            NotifyOperacaoJob::dispatch($payload);
            SyncCrmJob::dispatch('incident', $payload);
        }

        // Emergencias sempre notificam independente de severidade
        if ($categoryCode === 'emergency') {
            NotifyOperacaoJob::dispatch(array_merge($payload, ['type' => 'emergency']));
        }

        return $incidentId;
    }

    /**
     * Resolve um incidente.
     */
    public function resolveIncident(int $incidentId, string $resolution, int $resolvedBy): bool
    {
        $incident = DB::table('incidents')->where('id', $incidentId)->first();

        if (!$incident || $incident->resolved_at) {
            return false;
        }

        DB::table('incidents')
            ->where('id', $incidentId)
            ->update([
                'resolution' => $resolution,
                'resolved_at' => now()->toDateTimeString(),
                'resolved_by' => $resolvedBy,
                'updated_at' => now()->toDateTimeString(),
            ]);

        return true;
    }

    /**
     * Obtem incidentes pendentes de resolucao.
     */
    public function getPendingIncidents(?string $categoryCode = null): array
    {
        $query = DB::table('incidents')
            ->join('conversations', 'conversations.id', '=', 'incidents.conversation_id')
            ->join('contacts', 'contacts.id', '=', 'conversations.contact_id')
            ->join('domain_incident_severity', 'domain_incident_severity.id', '=', 'incidents.severity_id')
            ->join('domain_incident_category', 'domain_incident_category.id', '=', 'incidents.category_id')
            ->whereNull('incidents.resolved_at')
            ->select([
                'incidents.id',
                'incidents.conversation_id',
                'incidents.notes',
                'incidents.created_at',
                'domain_incident_severity.code as severity',
                'domain_incident_severity.label as severity_label',
                'domain_incident_category.code as category',
                'domain_incident_category.label as category_label',
                'contacts.name as contact_name',
                'contacts.phone as contact_phone',
            ]);

        if ($categoryCode) {
            $categoryId = $this->domainLookup->incidentCategoryId($categoryCode);
            $query->where('incidents.category_id', $categoryId);
        }

        return $query->orderByDesc('incidents.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Obtem estatisticas de incidentes.
     */
    public function getIncidentStats(string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $total = DB::table('incidents')
            ->where('created_at', '>=', $startDate)
            ->count();

        $resolved = DB::table('incidents')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('resolved_at')
            ->count();

        $byCategory = DB::table('incidents')
            ->join('domain_incident_category', 'domain_incident_category.id', '=', 'incidents.category_id')
            ->where('incidents.created_at', '>=', $startDate)
            ->selectRaw('domain_incident_category.code, domain_incident_category.label, COUNT(*) as total')
            ->groupBy('domain_incident_category.code', 'domain_incident_category.label')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $bySeverity = DB::table('incidents')
            ->join('domain_incident_severity', 'domain_incident_severity.id', '=', 'incidents.severity_id')
            ->where('incidents.created_at', '>=', $startDate)
            ->selectRaw('domain_incident_severity.code, domain_incident_severity.label, COUNT(*) as total')
            ->groupBy('domain_incident_severity.code', 'domain_incident_severity.label')
            ->get()
            ->pluck('total', 'code')
            ->toArray();

        $avgResolutionTime = DB::table('incidents')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');

        return [
            'period' => $period,
            'total' => $total,
            'resolved' => $resolved,
            'pending' => $total - $resolved,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
            'avg_resolution_minutes' => $avgResolutionTime ? round($avgResolutionTime) : 0,
            'by_category' => array_map(function ($item) use ($total) {
                return [
                    'code' => $item->code,
                    'label' => $item->label,
                    'count' => $item->total,
                    'percentage' => $total > 0 ? round(($item->total / $total) * 100, 1) : 0,
                ];
            }, $byCategory),
            'by_severity' => [
                'low' => $bySeverity['low'] ?? 0,
                'medium' => $bySeverity['medium'] ?? 0,
                'high' => $bySeverity['high'] ?? 0,
                'critical' => $bySeverity['critical'] ?? 0,
            ],
        ];
    }

    /**
     * Obtem incidentes criticos pendentes (para alertas).
     */
    public function getCriticalPending(): array
    {
        return DB::table('incidents')
            ->join('domain_incident_severity', 'domain_incident_severity.id', '=', 'incidents.severity_id')
            ->whereNull('incidents.resolved_at')
            ->whereIn('domain_incident_severity.code', ['high', 'critical'])
            ->select(['incidents.id', 'incidents.conversation_id', 'incidents.notes', 'incidents.created_at'])
            ->orderByDesc('incidents.created_at')
            ->get()
            ->toArray();
    }
}
