<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Checkin;
use App\Models\Checklist;
use App\Models\ChecklistEntry;
use App\Models\ServiceLog;
use App\Models\DomainCheckType;
use App\Models\DomainChecklistType;
use App\Models\DomainScheduleStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de check-in/out e checklists.
 */
class CheckinService
{
    public function __construct(
        protected ScheduleService $scheduleService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Realiza check-in do cuidador.
     */
    public function performCheckin(Schedule $schedule, ?string $location = null): Checkin
    {
        // Validacoes
        $this->validateCheckinAllowed($schedule);

        return DB::transaction(function () use ($schedule, $location) {
            // Cria registro de check-in
            $checkin = Checkin::create([
                'schedule_id' => $schedule->id,
                'check_type_id' => DomainCheckType::IN,
                'timestamp' => now(),
                'location' => $location,
            ]);

            // Atualiza status do agendamento
            $this->scheduleService->startSchedule($schedule);

            // Notifica cliente
            $this->notificationService->notifyServiceStart($schedule);

            Log::info('Check-in realizado', [
                'schedule_id' => $schedule->id,
                'checkin_id' => $checkin->id,
                'is_late' => $checkin->isLate(),
            ]);

            return $checkin;
        });
    }

    /**
     * Realiza check-out do cuidador.
     */
    public function performCheckout(Schedule $schedule, ?string $location = null, ?array $activities = null): Checkin
    {
        // Validacoes
        $this->validateCheckoutAllowed($schedule);

        return DB::transaction(function () use ($schedule, $location, $activities) {
            // Cria registro de check-out
            $checkout = Checkin::create([
                'schedule_id' => $schedule->id,
                'check_type_id' => DomainCheckType::OUT,
                'timestamp' => now(),
                'location' => $location,
            ]);

            // Registra atividades se fornecidas
            if (!empty($activities)) {
                $this->logActivities($schedule, $activities);
            }

            // Atualiza status do agendamento
            $this->scheduleService->completeSchedule($schedule);

            // Notifica cliente
            $this->notificationService->notifyServiceEnd($schedule);

            Log::info('Check-out realizado', [
                'schedule_id' => $schedule->id,
                'checkout_id' => $checkout->id,
            ]);

            return $checkout;
        });
    }

    /**
     * Valida se check-in e permitido.
     */
    protected function validateCheckinAllowed(Schedule $schedule): void
    {
        if (!$schedule->isPlanned()) {
            throw new \InvalidArgumentException('Agendamento nao esta planejado.');
        }

        if ($schedule->hasCheckedIn()) {
            throw new \InvalidArgumentException('Check-in ja foi realizado.');
        }

        $config = config('operacao.checkin');
        $now = Carbon::now();
        $scheduleStart = $schedule->start_date_time;

        // Verifica se nao esta muito cedo
        $earliestAllowed = $scheduleStart->copy()->subMinutes($config['early_tolerance_minutes']);
        if ($now->lt($earliestAllowed)) {
            throw new \InvalidArgumentException('Check-in antecipado demais. Aguarde o horario.');
        }
    }

    /**
     * Valida se check-out e permitido.
     */
    protected function validateCheckoutAllowed(Schedule $schedule): void
    {
        if (!$schedule->isInProgress()) {
            throw new \InvalidArgumentException('Agendamento nao esta em andamento.');
        }

        if (!$schedule->hasCheckedIn()) {
            throw new \InvalidArgumentException('Check-in nao foi realizado.');
        }

        if ($schedule->hasCheckedOut()) {
            throw new \InvalidArgumentException('Check-out ja foi realizado.');
        }
    }

    /**
     * Valida localizacao do check-in/out.
     */
    public function validateLocation(string $location, array $expectedLocation): bool
    {
        if (!config('operacao.checkin.require_location')) {
            return true;
        }

        // Formato esperado: "lat,lng"
        $parts = explode(',', $location);
        if (count($parts) !== 2) {
            return false;
        }

        $lat = floatval(trim($parts[0]));
        $lng = floatval(trim($parts[1]));

        $expectedLat = $expectedLocation['lat'] ?? 0;
        $expectedLng = $expectedLocation['lng'] ?? 0;

        // Calcula distancia em metros usando formula de Haversine simplificada
        $distance = $this->calculateDistance($lat, $lng, $expectedLat, $expectedLng);

        $maxDistance = config('operacao.checkin.max_distance_meters', 500);

        return $distance <= $maxDistance;
    }

    /**
     * Calcula distancia entre dois pontos em metros.
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // metros

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Cria checklist para uma solicitacao de servico.
     */
    public function createChecklist(int $serviceRequestId, int $type, array $items): Checklist
    {
        return DB::transaction(function () use ($serviceRequestId, $type, $items) {
            $checklist = Checklist::create([
                'service_request_id' => $serviceRequestId,
                'checklist_type_id' => $type,
                'template_json' => $items,
            ]);

            foreach ($items as $item) {
                ChecklistEntry::create([
                    'checklist_id' => $checklist->id,
                    'item_key' => $item['key'] ?? $item,
                    'completed' => false,
                    'notes' => null,
                ]);
            }

            return $checklist;
        });
    }

    /**
     * Atualiza item do checklist.
     */
    public function updateChecklistItem(int $entryId, bool $completed, ?string $notes = null): ChecklistEntry
    {
        $entry = ChecklistEntry::findOrFail($entryId);

        $entry->completed = $completed;
        if ($notes !== null) {
            $entry->notes = $notes;
        }
        $entry->save();

        Log::info('Item de checklist atualizado', [
            'entry_id' => $entryId,
            'completed' => $completed,
        ]);

        return $entry;
    }

    /**
     * Obtem checklist de inicio.
     */
    public function getStartChecklist(int $serviceRequestId): ?Checklist
    {
        return Checklist::where('service_request_id', $serviceRequestId)
            ->where('checklist_type_id', DomainChecklistType::START)
            ->with('entries')
            ->first();
    }

    /**
     * Obtem checklist de fim.
     */
    public function getEndChecklist(int $serviceRequestId): ?Checklist
    {
        return Checklist::where('service_request_id', $serviceRequestId)
            ->where('checklist_type_id', DomainChecklistType::END)
            ->with('entries')
            ->first();
    }

    /**
     * Registra atividades realizadas.
     */
    public function logActivities(Schedule $schedule, array $activities, ?string $notes = null): ServiceLog
    {
        $log = ServiceLog::create([
            'schedule_id' => $schedule->id,
            'activities_json' => array_map(function ($activity) {
                return [
                    'activity' => $activity,
                    'logged_at' => now()->toIso8601String(),
                ];
            }, $activities),
            'notes' => $notes,
            'created_at' => now(),
        ]);

        Log::info('Atividades registradas', [
            'schedule_id' => $schedule->id,
            'service_log_id' => $log->id,
            'activity_count' => count($activities),
        ]);

        return $log;
    }

    /**
     * Obtem logs de servico de um agendamento.
     */
    public function getServiceLogs(int $scheduleId): Collection
    {
        return ServiceLog::where('schedule_id', $scheduleId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Verifica atrasos e gera alertas.
     */
    public function checkDelays(): Collection
    {
        $lateSchedules = collect();
        $lateTolerance = config('operacao.checkin.late_tolerance_minutes', 15);
        $now = Carbon::now();

        // Busca agendamentos planejados que deveriam ter iniciado
        $overdueSchedules = Schedule::planned()
            ->today()
            ->get()
            ->filter(function ($schedule) use ($now, $lateTolerance) {
                $shouldHaveStarted = $schedule->start_date_time->addMinutes($lateTolerance);
                return $now->gt($shouldHaveStarted);
            });

        foreach ($overdueSchedules as $schedule) {
            $delayMinutes = $now->diffInMinutes($schedule->start_date_time);

            $lateSchedules->push([
                'schedule' => $schedule,
                'delay_minutes' => $delayMinutes,
                'caregiver_id' => $schedule->caregiver_id,
                'client_id' => $schedule->client_id,
            ]);

            Log::warning('Atraso detectado', [
                'schedule_id' => $schedule->id,
                'delay_minutes' => $delayMinutes,
            ]);
        }

        return $lateSchedules;
    }

    /**
     * Retorna templates de checklist padrao.
     */
    public function getDefaultChecklistTemplates(): array
    {
        return [
            'start' => [
                ['key' => 'confirm_arrival', 'label' => 'Confirmar chegada ao local'],
                ['key' => 'verify_client_condition', 'label' => 'Verificar condicao do cliente'],
                ['key' => 'check_medications', 'label' => 'Conferir medicacoes'],
                ['key' => 'note_special_needs', 'label' => 'Anotar necessidades especiais'],
                ['key' => 'safety_check', 'label' => 'Verificar seguranca do ambiente'],
            ],
            'end' => [
                ['key' => 'complete_activities', 'label' => 'Atividades planejadas concluidas'],
                ['key' => 'medication_administered', 'label' => 'Medicacoes administradas'],
                ['key' => 'report_incidents', 'label' => 'Relatar ocorrencias'],
                ['key' => 'client_stable', 'label' => 'Cliente em condicao estavel'],
                ['key' => 'handover_notes', 'label' => 'Notas de passagem de plantao'],
            ],
        ];
    }
}
