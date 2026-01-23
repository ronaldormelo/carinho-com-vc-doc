<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Substitution;
use App\Models\DomainAssignmentStatus;
use App\Models\DomainScheduleStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de substituicoes de cuidadores.
 */
class SubstitutionService
{
    public function __construct(
        protected MatchService $matchService,
        protected ScheduleService $scheduleService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Processa substituicao de cuidador.
     */
    public function processSubstitution(
        Assignment $assignment,
        string $reason,
        ?int $newCaregiverId = null
    ): array {
        return DB::transaction(function () use ($assignment, $reason, $newCaregiverId) {
            $oldCaregiverId = $assignment->caregiver_id;
            $serviceRequest = $assignment->serviceRequest;

            // Se nao foi especificado um substituto, busca automaticamente
            if (!$newCaregiverId) {
                $candidates = $this->matchService->findCandidates($serviceRequest);

                if ($candidates->isEmpty()) {
                    Log::warning('Nenhum substituto encontrado', [
                        'assignment_id' => $assignment->id,
                    ]);

                    return [
                        'success' => false,
                        'message' => 'Nenhum cuidador substituto disponivel.',
                        'assignment' => $assignment,
                    ];
                }

                $newCaregiverId = $candidates->first()['caregiver_id'];
            }

            // Marca alocacao atual como substituida
            $assignment->status_id = DomainAssignmentStatus::REPLACED;
            $assignment->save();

            // Cria nova alocacao
            $newAssignment = $this->matchService->assignCaregiver($serviceRequest, $newCaregiverId);

            // Registra substituicao
            $substitution = Substitution::create([
                'assignment_id' => $assignment->id,
                'old_caregiver_id' => $oldCaregiverId,
                'new_caregiver_id' => $newCaregiverId,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            // Transfere agendamentos futuros
            $this->transferSchedules($assignment, $newAssignment);

            // Notifica cliente se configurado
            if (config('operacao.substitution.auto_notify_client')) {
                $this->notificationService->notifyCaregiverReplaced($newAssignment, $oldCaregiverId);
            }

            Log::info('Substituicao processada', [
                'old_assignment_id' => $assignment->id,
                'new_assignment_id' => $newAssignment->id,
                'old_caregiver_id' => $oldCaregiverId,
                'new_caregiver_id' => $newCaregiverId,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'Substituicao realizada com sucesso.',
                'old_assignment' => $assignment,
                'new_assignment' => $newAssignment,
                'substitution' => $substitution,
            ];
        });
    }

    /**
     * Transfere agendamentos futuros para nova alocacao.
     */
    protected function transferSchedules(Assignment $oldAssignment, Assignment $newAssignment): void
    {
        $today = now()->toDateString();

        // Busca agendamentos futuros planejados
        $futureSchedules = Schedule::where('assignment_id', $oldAssignment->id)
            ->where('shift_date', '>=', $today)
            ->where('status_id', DomainScheduleStatus::PLANNED)
            ->get();

        foreach ($futureSchedules as $schedule) {
            // Atualiza para nova alocacao e cuidador
            $schedule->assignment_id = $newAssignment->id;
            $schedule->caregiver_id = $newAssignment->caregiver_id;
            $schedule->save();
        }

        Log::info('Agendamentos transferidos', [
            'count' => $futureSchedules->count(),
            'old_assignment_id' => $oldAssignment->id,
            'new_assignment_id' => $newAssignment->id,
        ]);
    }

    /**
     * Busca cuidadores disponiveis para substituicao urgente.
     */
    public function findUrgentSubstitutes(Assignment $assignment): Collection
    {
        $serviceRequest = $assignment->serviceRequest;
        $config = config('operacao.substitution');

        // Busca com prioridade para mesma regiao se configurado
        $requirements = [];
        if ($config['same_region_priority']) {
            // Busca regiao do cliente via integracao
            $requirements['prioritize_same_region'] = true;
        }

        return $this->matchService->findCandidates($serviceRequest, $requirements);
    }

    /**
     * Processa nao comparecimento (no-show).
     */
    public function processNoShow(Schedule $schedule): array
    {
        $assignment = $schedule->assignment;

        // Marca agendamento como perdido
        $this->scheduleService->markAsMissed($schedule);

        // Processa substituicao
        return $this->processSubstitution($assignment, 'no_show');
    }

    /**
     * Obtem historico de substituicoes de uma alocacao.
     */
    public function getSubstitutionHistory(int $assignmentId): Collection
    {
        return Substitution::where('assignment_id', $assignmentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtem estatisticas de substituicoes de um cuidador.
     */
    public function getCaregiverSubstitutionStats(int $caregiverId): array
    {
        $asOld = Substitution::fromCaregiver($caregiverId)->count();
        $asNew = Substitution::toCaregiver($caregiverId)->count();

        $byReason = Substitution::fromCaregiver($caregiverId)
            ->selectRaw('reason, COUNT(*) as count')
            ->groupBy('reason')
            ->pluck('count', 'reason')
            ->toArray();

        return [
            'substituted_times' => $asOld,
            'replaced_others_times' => $asNew,
            'by_reason' => $byReason,
        ];
    }

    /**
     * Verifica se substituicao e necessaria baseado em alertas.
     */
    public function checkSubstitutionNeeded(): Collection
    {
        $needsSubstitution = collect();
        $maxSearchTime = config('operacao.substitution.max_search_time_minutes', 120);

        // Busca agendamentos planejados com atrasos criticos
        $criticalDelays = app(CheckinService::class)->checkDelays();

        foreach ($criticalDelays as $delay) {
            if ($delay['delay_minutes'] >= $maxSearchTime) {
                $needsSubstitution->push([
                    'schedule' => $delay['schedule'],
                    'reason' => 'excessive_delay',
                    'delay_minutes' => $delay['delay_minutes'],
                ]);
            }
        }

        return $needsSubstitution;
    }

    /**
     * Cancela substituicao pendente.
     */
    public function cancelSubstitution(Substitution $substitution): bool
    {
        // Verifica se pode cancelar (se a nova alocacao ainda esta ativa)
        $assignment = Assignment::find($substitution->assignment_id);

        if (!$assignment) {
            return false;
        }

        // Reverte status se possivel
        $newAssignment = Assignment::forCaregiver($substitution->new_caregiver_id)
            ->where('service_request_id', $assignment->service_request_id)
            ->active()
            ->first();

        if ($newAssignment) {
            DB::transaction(function () use ($assignment, $newAssignment, $substitution) {
                // Cancela nova alocacao
                $newAssignment->status_id = DomainAssignmentStatus::CANCELED;
                $newAssignment->save();

                // Reativa alocacao original
                $assignment->status_id = DomainAssignmentStatus::ASSIGNED;
                $assignment->save();

                // Transfere agendamentos de volta
                Schedule::where('assignment_id', $newAssignment->id)
                    ->update([
                        'assignment_id' => $assignment->id,
                        'caregiver_id' => $assignment->caregiver_id,
                    ]);
            });

            Log::info('Substituicao cancelada', [
                'substitution_id' => $substitution->id,
            ]);

            return true;
        }

        return false;
    }
}
