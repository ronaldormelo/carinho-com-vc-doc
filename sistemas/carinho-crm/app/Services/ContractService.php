<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Domain\DomainContractStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContractService
{
    /**
     * Cria um novo contrato
     */
    public function createContract(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            // Status inicial é "draft"
            $data['status_id'] = $data['status_id'] ?? DomainContractStatus::DRAFT;

            $contract = Contract::create($data);

            Log::channel('audit')->info('Contrato criado', [
                'contract_id' => $contract->id,
                'client_id' => $data['client_id'],
            ]);

            return $contract;
        });
    }

    /**
     * Atualiza um contrato existente
     */
    public function updateContract(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data) {
            $contract->update($data);

            Log::channel('audit')->info('Contrato atualizado', [
                'contract_id' => $contract->id,
                'changes' => $contract->getChanges(),
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Registra assinatura do contrato (aceite digital)
     */
    public function signContract(Contract $contract, string $ipAddress, string $userAgent): Contract
    {
        if (!$contract->isDraft()) {
            throw new \InvalidArgumentException('Contrato já foi assinado');
        }

        return DB::transaction(function () use ($contract, $ipAddress, $userAgent) {
            $contract->status_id = DomainContractStatus::SIGNED;
            $contract->signed_at = now();
            $contract->save();

            // Log de auditoria com informações de rastreabilidade
            Log::channel('audit')->info('Contrato assinado digitalmente', [
                'contract_id' => $contract->id,
                'client_id' => $contract->client_id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'signed_at' => $contract->signed_at->toIso8601String(),
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Ativa um contrato assinado
     */
    public function activateContract(Contract $contract): Contract
    {
        if (!$contract->isSigned()) {
            throw new \InvalidArgumentException('Contrato precisa estar assinado');
        }

        return DB::transaction(function () use ($contract) {
            $contract->status_id = DomainContractStatus::ACTIVE;
            $contract->save();

            Log::channel('audit')->info('Contrato ativado', [
                'contract_id' => $contract->id,
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Encerra um contrato
     */
    public function closeContract(Contract $contract, ?string $reason = null): Contract
    {
        if (!$contract->isVigente()) {
            throw new \InvalidArgumentException('Contrato não está vigente');
        }

        return DB::transaction(function () use ($contract, $reason) {
            $contract->status_id = DomainContractStatus::CLOSED;
            $contract->end_date = today();
            $contract->save();

            Log::channel('audit')->info('Contrato encerrado', [
                'contract_id' => $contract->id,
                'reason' => $reason,
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Gera link único para aceite digital
     */
    public function generateSignatureLink(Contract $contract): string
    {
        if (!$contract->isDraft()) {
            throw new \InvalidArgumentException('Contrato já foi assinado');
        }

        // Gera token único
        $token = Str::random(64);
        
        // Armazena token (pode usar cache ou tabela)
        cache()->put(
            "contract_signature:{$token}",
            [
                'contract_id' => $contract->id,
                'created_at' => now()->toIso8601String(),
            ],
            now()->addDays(7)
        );

        return url("/contract/{$token}/sign");
    }

    /**
     * Valida token de assinatura
     */
    public function validateSignatureToken(string $token): ?Contract
    {
        $data = cache()->get("contract_signature:{$token}");

        if (!$data) {
            return null;
        }

        return Contract::find($data['contract_id']);
    }

    /**
     * Processa aceite digital via token
     */
    public function processDigitalAcceptance(string $token, string $ipAddress, string $userAgent): Contract
    {
        $contract = $this->validateSignatureToken($token);

        if (!$contract) {
            throw new \InvalidArgumentException('Token inválido ou expirado');
        }

        if (!$contract->isDraft()) {
            throw new \InvalidArgumentException('Contrato já foi assinado');
        }

        // Assina o contrato
        $contract = $this->signContract($contract, $ipAddress, $userAgent);

        // Remove token do cache
        cache()->forget("contract_signature:{$token}");

        return $contract;
    }

    /**
     * Obtém contratos que expiram em breve
     */
    public function getExpiringContracts(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Contract::with(['client.lead', 'proposal'])
            ->expiringIn($days)
            ->orderBy('end_date', 'asc')
            ->get();
    }

    /**
     * Obtém estatísticas de contratos
     */
    public function getStatistics($startDate = null, $endDate = null): array
    {
        $query = Contract::query();

        if ($startDate && $endDate) {
            $query->whereBetween('signed_at', [$startDate, $endDate]);
        }

        $total = $query->count();
        $active = Contract::active()->count();
        $signed = Contract::signed()->count();
        $closed = Contract::closed()->count();

        // Valor total dos contratos ativos
        $activeValue = Contract::active()
            ->with('proposal')
            ->get()
            ->sum(fn($c) => $c->proposal?->price ?? 0);

        // Contratos expirando nos próximos 30 dias
        $expiringSoon = Contract::expiringIn(30)->count();

        return [
            'total' => $total,
            'active' => $active,
            'signed' => $signed,
            'closed' => $closed,
            'active_value' => round($activeValue, 2),
            'expiring_soon' => $expiringSoon,
        ];
    }
}
