<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Domain\DomainLeadStatus;
use App\Models\LossReason;
use App\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class LeadService
{
    public function __construct(
        protected ?LeadRepositoryInterface $repository = null
    ) {}

    /**
     * Cria um novo lead
     */
    public function createLead(array $data): Lead
    {
        return DB::transaction(function () use ($data) {
            // Define status inicial como "new"
            $data['status_id'] = $data['status_id'] ?? DomainLeadStatus::NEW;

            $lead = Lead::create($data);

            Log::channel('audit')->info('Lead criado', [
                'lead_id' => $lead->id,
                'source' => $data['source'] ?? 'unknown',
            ]);

            return $lead;
        });
    }

    /**
     * Atualiza um lead existente
     */
    public function updateLead(Lead $lead, array $data): Lead
    {
        return DB::transaction(function () use ($lead, $data) {
            $lead->update($data);

            Log::channel('audit')->info('Lead atualizado', [
                'lead_id' => $lead->id,
                'changes' => $lead->getChanges(),
            ]);

            return $lead->fresh();
        });
    }

    /**
     * Marca um lead como perdido
     */
    public function markAsLost(Lead $lead, string $reason, ?string $details = null): Lead
    {
        return DB::transaction(function () use ($lead, $reason, $details) {
            $lead->status_id = DomainLeadStatus::LOST;
            $lead->save();

            LossReason::updateOrCreate(
                ['lead_id' => $lead->id],
                [
                    'reason' => $reason,
                    'details' => $details,
                ]
            );

            Log::channel('audit')->info('Lead marcado como perdido', [
                'lead_id' => $lead->id,
                'reason' => $reason,
            ]);

            return $lead->fresh();
        });
    }

    /**
     * Converte lead em cliente
     */
    public function convertToClient(Lead $lead, array $clientData): Lead
    {
        return DB::transaction(function () use ($lead, $clientData) {
            // Atualiza status do lead
            $lead->status_id = DomainLeadStatus::ACTIVE;
            $lead->save();

            // Cria o cliente
            $clientData['lead_id'] = $lead->id;
            $clientData['phone'] = $clientData['phone'] ?? $lead->phone;
            $clientData['city'] = $clientData['city'] ?? $lead->city;
            $clientData['primary_contact'] = $clientData['primary_contact'] ?? $lead->name;

            $lead->client()->create($clientData);

            Log::channel('audit')->info('Lead convertido em cliente', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client->id,
            ]);

            return $lead->fresh(['client']);
        });
    }

    /**
     * Busca leads por termo
     */
    public function search(string $term, int $limit = 20): Collection
    {
        return Lead::with(['status', 'urgency', 'serviceType'])
            ->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%")
                      ->orWhere('city', 'LIKE', "%{$term}%")
                      ->orWhere('source', 'LIKE', "%{$term}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém estatísticas gerais de leads
     */
    public function getStatistics(): array
    {
        $total = Lead::count();
        $byStatus = Lead::selectRaw('status_id, COUNT(*) as count')
            ->groupBy('status_id')
            ->pluck('count', 'status_id')
            ->toArray();

        $bySource = Lead::selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'source')
            ->toArray();

        $byCity = Lead::selectRaw('city, COUNT(*) as count')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'city')
            ->toArray();

        $byServiceType = Lead::selectRaw('service_type_id, COUNT(*) as count')
            ->groupBy('service_type_id')
            ->pluck('count', 'service_type_id')
            ->toArray();

        $inPipeline = Lead::inPipeline()->count();
        $converted = Lead::active()->count();
        $lost = Lead::lost()->count();

        $conversionRate = $total > 0 ? round(($converted / $total) * 100, 2) : 0;

        // Leads criados hoje
        $todayCount = Lead::whereDate('created_at', today())->count();
        
        // Leads criados esta semana
        $weekCount = Lead::whereBetween('created_at', [now()->startOfWeek(), now()])->count();

        // Leads criados este mês
        $monthCount = Lead::whereBetween('created_at', [now()->startOfMonth(), now()])->count();

        return [
            'total' => $total,
            'in_pipeline' => $inPipeline,
            'converted' => $converted,
            'lost' => $lost,
            'conversion_rate' => $conversionRate,
            'by_status' => $byStatus,
            'by_source' => $bySource,
            'by_city' => $byCity,
            'by_service_type' => $byServiceType,
            'today' => $todayCount,
            'this_week' => $weekCount,
            'this_month' => $monthCount,
        ];
    }

    /**
     * Obtém leads que precisam de follow-up
     */
    public function getLeadsNeedingFollowUp(int $daysWithoutContact = 3): Collection
    {
        return Lead::with(['status', 'interactions'])
            ->inPipeline()
            ->whereDoesntHave('interactions', function ($query) use ($daysWithoutContact) {
                $query->where('occurred_at', '>=', now()->subDays($daysWithoutContact));
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Obtém leads urgentes (urgência = hoje)
     */
    public function getUrgentLeads(): Collection
    {
        return Lead::with(['status', 'urgency', 'serviceType'])
            ->urgent()
            ->inPipeline()
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
