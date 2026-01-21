<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Domain\DomainContractStatus;
use App\Services\ContractService;
use App\Events\ContractSigned;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(
        protected ContractService $contractService
    ) {}

    /**
     * Lista todos os contratos com filtros e paginação
     */
    public function index(Request $request)
    {
        $query = Contract::with(['client.lead', 'proposal.serviceType', 'status']);

        // Filtros
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('vigente') && $request->vigente) {
            $query->vigente();
        }

        if ($request->has('expiring_in')) {
            $query->expiringIn((int) $request->expiring_in);
        }

        if ($request->has('expired') && $request->expired) {
            $query->expired();
        }

        // Ordenação
        $sortField = $request->get('sort_by', 'end_date');
        $sortDirection = $request->get('sort_dir', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Paginação
        $perPage = min($request->get('per_page', 15), 100);
        $contracts = $query->paginate($perPage);

        return ContractResource::collection($contracts);
    }

    /**
     * Cria um novo contrato
     */
    public function store(ContractRequest $request)
    {
        $contract = $this->contractService->createContract($request->validated());

        return $this->createdResponse(
            new ContractResource($contract->load(['client.lead', 'proposal.serviceType', 'status'])),
            'Contrato criado com sucesso'
        );
    }

    /**
     * Exibe um contrato específico
     */
    public function show(Contract $contract)
    {
        $contract->load([
            'client.lead',
            'client.careNeeds.patientType',
            'proposal.serviceType',
            'proposal.deal.lead',
            'status',
        ]);

        return new ContractResource($contract);
    }

    /**
     * Atualiza um contrato
     */
    public function update(ContractRequest $request, Contract $contract)
    {
        $contract = $this->contractService->updateContract($contract, $request->validated());

        return $this->successResponse(
            new ContractResource($contract->load(['client.lead', 'proposal.serviceType', 'status'])),
            'Contrato atualizado com sucesso'
        );
    }

    /**
     * Remove um contrato (apenas rascunhos)
     */
    public function destroy(Contract $contract)
    {
        if (!$contract->isDraft()) {
            return $this->errorResponse(
                'Apenas contratos em rascunho podem ser excluídos',
                422
            );
        }

        $contract->delete();

        return $this->successResponse(null, 'Contrato excluído com sucesso');
    }

    /**
     * Registra assinatura do contrato (aceite digital)
     */
    public function sign(Request $request, Contract $contract)
    {
        if (!$contract->isDraft()) {
            return $this->errorResponse('Contrato já foi assinado', 422);
        }

        $request->validate([
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:500',
        ]);

        $contract = $this->contractService->signContract(
            $contract,
            $request->ip_address ?? request()->ip(),
            $request->user_agent ?? request()->userAgent()
        );

        event(new ContractSigned($contract));

        return $this->successResponse(
            new ContractResource($contract->load(['client.lead', 'proposal.serviceType', 'status'])),
            'Contrato assinado com sucesso'
        );
    }

    /**
     * Ativa um contrato assinado
     */
    public function activate(Contract $contract)
    {
        if (!$contract->isSigned()) {
            return $this->errorResponse('Contrato precisa estar assinado para ser ativado', 422);
        }

        $contract->status_id = DomainContractStatus::ACTIVE;
        $contract->save();

        return $this->successResponse(
            new ContractResource($contract->load(['client.lead', 'proposal.serviceType', 'status'])),
            'Contrato ativado com sucesso'
        );
    }

    /**
     * Encerra um contrato
     */
    public function close(Request $request, Contract $contract)
    {
        if (!$contract->isVigente()) {
            return $this->errorResponse('Contrato não está vigente', 422);
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $contract = $this->contractService->closeContract($contract, $request->reason);

        return $this->successResponse(
            new ContractResource($contract->load(['client.lead', 'proposal.serviceType', 'status'])),
            'Contrato encerrado com sucesso'
        );
    }

    /**
     * Lista contratos que expiram em breve
     */
    public function expiringSoon(Request $request)
    {
        $days = $request->get('days', 30);

        $contracts = Contract::with(['client.lead', 'proposal.serviceType', 'status'])
            ->expiringIn($days)
            ->orderBy('end_date', 'asc')
            ->get();

        return ContractResource::collection($contracts);
    }

    /**
     * Gera link para aceite digital
     */
    public function generateSignatureLink(Contract $contract)
    {
        if (!$contract->isDraft()) {
            return $this->errorResponse('Contrato já foi assinado', 422);
        }

        $link = $this->contractService->generateSignatureLink($contract);

        return $this->successResponse([
            'link' => $link,
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ]);
    }
}
