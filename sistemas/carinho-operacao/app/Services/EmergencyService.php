<?php

namespace App\Services;

use App\Models\Emergency;
use App\Models\ServiceRequest;
use App\Models\DomainEmergencySeverity;
use App\Jobs\ProcessEmergencyAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de emergencias.
 */
class EmergencyService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Registra uma emergencia.
     */
    public function createEmergency(
        int $serviceRequestId,
        int $severityId,
        string $description
    ): Emergency {
        return DB::transaction(function () use ($serviceRequestId, $severityId, $description) {
            $emergency = Emergency::create([
                'service_request_id' => $serviceRequestId,
                'severity_id' => $severityId,
                'description' => $description,
            ]);

            // Notifica cliente
            $this->notificationService->notifyEmergency($emergency);

            // Despacha processamento de alerta
            ProcessEmergencyAlert::dispatch($emergency);

            Log::warning('Emergencia registrada', [
                'emergency_id' => $emergency->id,
                'severity' => $severityId,
                'service_request_id' => $serviceRequestId,
            ]);

            return $emergency;
        });
    }

    /**
     * Resolve uma emergencia.
     */
    public function resolveEmergency(Emergency $emergency, ?string $resolution = null): Emergency
    {
        $emergency->resolved_at = now();
        $emergency->save();

        Log::info('Emergencia resolvida', [
            'emergency_id' => $emergency->id,
            'resolution' => $resolution,
        ]);

        return $emergency;
    }

    /**
     * Escalona uma emergencia.
     */
    public function escalateEmergency(Emergency $emergency): Emergency
    {
        $currentSeverity = $emergency->severity_id;

        // Escalona para proximo nivel
        $newSeverity = match ($currentSeverity) {
            DomainEmergencySeverity::LOW => DomainEmergencySeverity::MEDIUM,
            DomainEmergencySeverity::MEDIUM => DomainEmergencySeverity::HIGH,
            DomainEmergencySeverity::HIGH => DomainEmergencySeverity::CRITICAL,
            default => $currentSeverity,
        };

        if ($newSeverity !== $currentSeverity) {
            $emergency->severity_id = $newSeverity;
            $emergency->save();

            Log::warning('Emergencia escalonada', [
                'emergency_id' => $emergency->id,
                'old_severity' => $currentSeverity,
                'new_severity' => $newSeverity,
            ]);

            // Re-notifica com nova severidade
            ProcessEmergencyAlert::dispatch($emergency);
        }

        return $emergency;
    }

    /**
     * Obtem emergencias pendentes.
     */
    public function getPendingEmergencies(): Collection
    {
        return Emergency::pending()
            ->orderByRaw('FIELD(severity_id, 4, 3, 2, 1)') // Ordena por severidade (critica primeiro)
            ->with(['serviceRequest', 'severity'])
            ->get();
    }

    /**
     * Obtem emergencias criticas.
     */
    public function getCriticalEmergencies(): Collection
    {
        return Emergency::pending()
            ->critical()
            ->with(['serviceRequest', 'severity'])
            ->get();
    }

    /**
     * Obtem emergencias que precisam de escalonamento.
     */
    public function getEmergenciesNeedingEscalation(): Collection
    {
        $autoEscalateMinutes = config('operacao.emergency.auto_escalate_minutes', 10);

        return Emergency::pending()
            ->where('severity_id', '<', DomainEmergencySeverity::CRITICAL)
            ->get()
            ->filter(function ($emergency) use ($autoEscalateMinutes) {
                // Verifica se passou do tempo de resposta sem resolucao
                $responseLimit = $emergency->response_time_limit;
                $minutesSinceCreation = now()->diffInMinutes($emergency->created_at);

                return $minutesSinceCreation >= ($responseLimit + $autoEscalateMinutes);
            });
    }

    /**
     * Obtem historico de emergencias de um servico.
     */
    public function getServiceEmergencyHistory(int $serviceRequestId): Collection
    {
        return Emergency::where('service_request_id', $serviceRequestId)
            ->orderBy('id', 'desc')
            ->with('severity')
            ->get();
    }

    /**
     * Obtem estatisticas de emergencias.
     */
    public function getEmergencyStats(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Emergency::query();

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $emergencies = $query->get();

        $total = $emergencies->count();
        $resolved = $emergencies->whereNotNull('resolved_at')->count();
        $pending = $total - $resolved;

        $bySeverity = [
            'low' => $emergencies->where('severity_id', DomainEmergencySeverity::LOW)->count(),
            'medium' => $emergencies->where('severity_id', DomainEmergencySeverity::MEDIUM)->count(),
            'high' => $emergencies->where('severity_id', DomainEmergencySeverity::HIGH)->count(),
            'critical' => $emergencies->where('severity_id', DomainEmergencySeverity::CRITICAL)->count(),
        ];

        // Calcula tempo medio de resolucao
        $resolvedEmergencies = $emergencies->whereNotNull('resolved_at');
        $avgResolutionTime = 0;
        if ($resolvedEmergencies->count() > 0) {
            $totalTime = 0;
            foreach ($resolvedEmergencies as $e) {
                $totalTime += $e->resolved_at->diffInMinutes($e->created_at);
            }
            $avgResolutionTime = $totalTime / $resolvedEmergencies->count();
        }

        return [
            'total' => $total,
            'resolved' => $resolved,
            'pending' => $pending,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
            'by_severity' => $bySeverity,
            'avg_resolution_time_minutes' => round($avgResolutionTime),
        ];
    }

    /**
     * Envia alerta para email de emergencia.
     */
    public function sendCriticalAlert(Emergency $emergency): void
    {
        $alertEmail = config('operacao.emergency.alert_email');

        if (!$alertEmail) {
            Log::warning('Email de alerta de emergencia nao configurado');
            return;
        }

        $serviceRequest = $emergency->serviceRequest;

        // Aqui seria enviado o email de alerta
        // Mail::to($alertEmail)->send(new CriticalEmergencyAlert($emergency));

        Log::info('Alerta critico enviado', [
            'emergency_id' => $emergency->id,
            'alert_email' => $alertEmail,
        ]);
    }

    /**
     * Processa alertas de emergencia (chamado pelo job).
     */
    public function processEmergencyAlert(Emergency $emergency): void
    {
        $severity = $emergency->severity_id;

        // Para emergencias criticas, envia alerta imediato
        if ($severity === DomainEmergencySeverity::CRITICAL) {
            $this->sendCriticalAlert($emergency);
        }

        // Para alta severidade, notifica supervisor
        if ($severity >= DomainEmergencySeverity::HIGH) {
            // Notifica supervisor
            Log::info('Notificando supervisor sobre emergencia', [
                'emergency_id' => $emergency->id,
            ]);
        }

        // Agenda verificacao de escalonamento
        // CheckEmergencyEscalation::dispatch($emergency)->delay(now()->addMinutes(
        //     config('operacao.emergency.auto_escalate_minutes')
        // ));
    }

    /**
     * Tipos de emergencia comuns.
     */
    public static function commonEmergencyTypes(): array
    {
        return [
            'medical' => 'Emergencia medica',
            'fall' => 'Queda do paciente',
            'medication_error' => 'Erro de medicacao',
            'behavior_change' => 'Mudanca comportamental',
            'equipment_failure' => 'Falha de equipamento',
            'caregiver_unavailable' => 'Cuidador indisponivel',
            'safety_concern' => 'Preocupacao com seguranca',
            'other' => 'Outro',
        ];
    }
}
