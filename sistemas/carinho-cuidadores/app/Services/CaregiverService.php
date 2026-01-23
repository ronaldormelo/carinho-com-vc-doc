<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\CaregiverSkill;
use App\Models\CaregiverAvailability;
use App\Models\CaregiverRegion;
use App\Models\CaregiverStatusHistory;
use App\Models\DomainCaregiverStatus;
use App\Models\DomainCareType;
use App\Models\DomainSkillLevel;
use App\Jobs\SyncCaregiverWithCrm;
use App\Jobs\SendCaregiverNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaregiverService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Cria novo cuidador com dados relacionados.
     */
    public function create(array $data): Caregiver
    {
        return DB::transaction(function () use ($data) {
            $pendingStatus = DomainCaregiverStatus::pending();

            // Normaliza CPF se informado
            $cpf = isset($data['cpf']) ? Caregiver::normalizeCpf($data['cpf']) : null;

            $caregiver = Caregiver::create([
                'name' => $data['name'],
                'phone' => $this->normalizePhone($data['phone']),
                'cpf' => $cpf,
                'birth_date' => $data['birth_date'] ?? null,
                'email' => $data['email'] ?? null,
                'city' => $data['city'],
                'address_street' => $data['address_street'] ?? null,
                'address_number' => $data['address_number'] ?? null,
                'address_complement' => $data['address_complement'] ?? null,
                'address_neighborhood' => $data['address_neighborhood'] ?? null,
                'address_zipcode' => $data['address_zipcode'] ?? null,
                'address_state' => $data['address_state'] ?? null,
                'status_id' => $pendingStatus->id,
                'experience_years' => $data['experience_years'] ?? 0,
                'profile_summary' => $data['profile_summary'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'recruitment_source' => $data['recruitment_source'] ?? null,
                'referred_by_caregiver_id' => $data['referred_by_caregiver_id'] ?? null,
                'created_at' => now(),
            ]);

            // Registra historico de status inicial
            $this->logStatusChange($caregiver, $pendingStatus->id);

            // Adiciona habilidades
            if (!empty($data['skills'])) {
                $this->syncSkills($caregiver, $data['skills']);
            }

            // Adiciona disponibilidade
            if (!empty($data['availability'])) {
                $this->syncAvailability($caregiver, $data['availability']);
            }

            // Adiciona regioes
            if (!empty($data['regions'])) {
                $this->syncRegions($caregiver, $data['regions']);
            }

            // Envia notificacao de boas-vindas
            SendCaregiverNotification::dispatch($caregiver, 'welcome');

            // Sincroniza com CRM
            SyncCaregiverWithCrm::dispatch($caregiver, 'created');

            Log::info('Cuidador criado', ['caregiver_id' => $caregiver->id]);

            return $caregiver->load(['status', 'skills.careType', 'availability', 'regions']);
        });
    }

    /**
     * Atualiza dados do cuidador.
     */
    public function update(Caregiver $caregiver, array $data): Caregiver
    {
        $caregiver->update([
            'name' => $data['name'] ?? $caregiver->name,
            'phone' => isset($data['phone']) ? $this->normalizePhone($data['phone']) : $caregiver->phone,
            'email' => $data['email'] ?? $caregiver->email,
            'city' => $data['city'] ?? $caregiver->city,
            'experience_years' => $data['experience_years'] ?? $caregiver->experience_years,
            'profile_summary' => $data['profile_summary'] ?? $caregiver->profile_summary,
            'updated_at' => now(),
        ]);

        // Sincroniza com CRM
        SyncCaregiverWithCrm::dispatch($caregiver, 'updated');

        Log::info('Cuidador atualizado', ['caregiver_id' => $caregiver->id]);

        return $caregiver;
    }

    /**
     * Altera status do cuidador.
     */
    public function changeStatus(Caregiver $caregiver, string $statusCode, ?string $reason = null): array
    {
        $newStatus = DomainCaregiverStatus::byCode($statusCode);

        if (!$newStatus) {
            return ['success' => false, 'message' => 'Status invalido'];
        }

        $oldStatusId = $caregiver->status_id;

        // Validacoes especificas
        if ($statusCode === 'active') {
            // Verifica se pode ser ativado
            if (!$caregiver->has_all_required_documents) {
                return [
                    'success' => false,
                    'message' => 'Cuidador precisa ter todos os documentos obrigatorios aprovados',
                ];
            }

            // Verifica se tem contrato ativo
            $hasActiveContract = $caregiver->contracts()
                ->whereHas('status', fn ($q) => $q->whereIn('code', ['signed', 'active']))
                ->exists();

            if (!$hasActiveContract) {
                return [
                    'success' => false,
                    'message' => 'Cuidador precisa ter contrato assinado',
                ];
            }
        }

        $caregiver->update([
            'status_id' => $newStatus->id,
            'updated_at' => now(),
        ]);

        // Registra historico
        $this->logStatusChange($caregiver, $newStatus->id);

        // Notifica cuidador
        $notificationMap = [
            'active' => 'activated',
            'inactive' => 'deactivated',
            'blocked' => 'blocked',
        ];

        if (isset($notificationMap[$statusCode])) {
            SendCaregiverNotification::dispatch(
                $caregiver,
                $notificationMap[$statusCode],
                ['reason' => $reason]
            );
        }

        // Sincroniza com CRM
        SyncCaregiverWithCrm::dispatch($caregiver, 'status_changed');

        Log::info('Status do cuidador alterado', [
            'caregiver_id' => $caregiver->id,
            'old_status_id' => $oldStatusId,
            'new_status_id' => $newStatus->id,
            'reason' => $reason,
        ]);

        return ['success' => true, 'message' => 'Status alterado com sucesso'];
    }

    /**
     * Sincroniza habilidades do cuidador.
     */
    public function syncSkills(Caregiver $caregiver, array $skills): void
    {
        $caregiver->skills()->delete();

        foreach ($skills as $skillData) {
            $careType = DomainCareType::byCode($skillData['care_type_code']);
            $level = DomainSkillLevel::byCode($skillData['level_code']);

            if ($careType && $level) {
                CaregiverSkill::create([
                    'caregiver_id' => $caregiver->id,
                    'care_type_id' => $careType->id,
                    'level_id' => $level->id,
                ]);
            }
        }
    }

    /**
     * Sincroniza disponibilidade do cuidador.
     */
    public function syncAvailability(Caregiver $caregiver, array $availability): void
    {
        $caregiver->availability()->delete();

        foreach ($availability as $item) {
            CaregiverAvailability::create([
                'caregiver_id' => $caregiver->id,
                'day_of_week' => $item['day_of_week'],
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
            ]);
        }
    }

    /**
     * Sincroniza regioes do cuidador.
     */
    public function syncRegions(Caregiver $caregiver, array $regions): void
    {
        $caregiver->regions()->delete();

        foreach ($regions as $item) {
            CaregiverRegion::create([
                'caregiver_id' => $caregiver->id,
                'city' => $item['city'],
                'neighborhood' => $item['neighborhood'] ?? null,
            ]);
        }
    }

    /**
     * Registra mudanca de status no historico.
     */
    private function logStatusChange(Caregiver $caregiver, int $statusId): void
    {
        CaregiverStatusHistory::create([
            'caregiver_id' => $caregiver->id,
            'status_id' => $statusId,
            'changed_at' => now(),
        ]);
    }

    /**
     * Normaliza numero de telefone.
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }
}
