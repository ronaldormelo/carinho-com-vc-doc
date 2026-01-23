<?php

namespace App\Jobs;

use App\Models\CaregiverContract;
use App\Models\DomainCaregiverStatus;
use App\Integrations\Integracoes\IntegracoesClient;
use App\Integrations\Operacao\OperacaoClient;
use App\Jobs\SendCaregiverNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessContractSign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        private CaregiverContract $contract
    ) {
        $this->onQueue('contracts');
    }

    public function handle(
        IntegracoesClient $integracoesClient,
        OperacaoClient $operacaoClient
    ): void {
        Log::info('Processando assinatura de contrato', [
            'contract_id' => $this->contract->id,
            'caregiver_id' => $this->contract->caregiver_id,
        ]);

        $caregiver = $this->contract->caregiver;

        // Publica evento de contrato assinado
        $integracoesClient->contractSigned(
            $this->contract->caregiver_id,
            $this->contract->id
        );

        // Verifica se cuidador pode ser ativado automaticamente
        if ($this->shouldAutoActivate($caregiver)) {
            $this->activateCaregiver($caregiver, $operacaoClient);
        }

        Log::info('Processamento de contrato concluido', [
            'contract_id' => $this->contract->id,
        ]);
    }

    private function shouldAutoActivate($caregiver): bool
    {
        if (!config('cuidadores.ativacao.auto_ativar_apos_validacao')) {
            return false;
        }

        // Verifica se tem todos os documentos
        if (!$caregiver->has_all_required_documents) {
            return false;
        }

        // Verifica se esta pendente
        return $caregiver->status?->code === 'pending';
    }

    private function activateCaregiver($caregiver, OperacaoClient $operacaoClient): void
    {
        $activeStatus = DomainCaregiverStatus::active();

        $caregiver->update([
            'status_id' => $activeStatus->id,
            'updated_at' => now(),
        ]);

        // Registra historico
        $caregiver->statusHistory()->create([
            'status_id' => $activeStatus->id,
            'changed_at' => now(),
        ]);

        // Notifica sistema de operacao
        $operacaoClient->notifyCaregiverActivated($caregiver->id, [
            'name' => $caregiver->name,
            'phone' => $caregiver->phone,
            'city' => $caregiver->city,
            'skills' => $caregiver->skills->map(fn ($s) => $s->careType?->code)->toArray(),
            'regions' => $caregiver->regions->map(fn ($r) => [
                'city' => $r->city,
                'neighborhood' => $r->neighborhood,
            ])->toArray(),
        ]);

        // Notifica cuidador
        SendCaregiverNotification::dispatch($caregiver, 'activated');

        Log::info('Cuidador ativado automaticamente apos assinatura de contrato', [
            'caregiver_id' => $caregiver->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job de processamento de contrato falhou', [
            'contract_id' => $this->contract->id,
            'caregiver_id' => $this->contract->caregiver_id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'contract-sign',
            'contract:' . $this->contract->id,
            'caregiver:' . $this->contract->caregiver_id,
        ];
    }
}
