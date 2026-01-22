<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverContract;
use App\Models\DomainContractStatus;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    public function __construct(
        private ContractService $contractService
    ) {}

    /**
     * Lista contratos de um cuidador.
     */
    public function index(int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $contracts = $caregiver->contracts()
            ->with(['status'])
            ->orderBy('id', 'desc')
            ->get();

        return $this->success([
            'contracts' => $contracts,
            'has_active_contract' => $contracts->contains(fn ($c) => $c->is_active),
        ]);
    }

    /**
     * Cria novo contrato/termo para o cuidador.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_type' => 'required|string|in:termo_responsabilidade,contrato_prestacao',
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        $result = $this->contractService->createContract(
            $caregiver,
            $request->get('contract_type')
        );

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result['contract'], 'Contrato criado com sucesso', 201);
    }

    /**
     * Exibe contrato especifico.
     */
    public function show(int $caregiverId, int $contractId): JsonResponse
    {
        $contract = CaregiverContract::where('caregiver_id', $caregiverId)
            ->where('id', $contractId)
            ->with(['status', 'caregiver'])
            ->first();

        if (!$contract) {
            return $this->error('Contrato nao encontrado', 404);
        }

        return $this->success($contract);
    }

    /**
     * Registra assinatura do contrato.
     */
    public function sign(Request $request, int $caregiverId, int $contractId): JsonResponse
    {
        $contract = CaregiverContract::where('caregiver_id', $caregiverId)
            ->where('id', $contractId)
            ->first();

        if (!$contract) {
            return $this->error('Contrato nao encontrado', 404);
        }

        $result = $this->contractService->signContract($contract, $request->all());

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(
            $contract->fresh(['status']),
            'Contrato assinado com sucesso'
        );
    }

    /**
     * Ativa contrato apos assinatura.
     */
    public function activate(int $caregiverId, int $contractId): JsonResponse
    {
        $contract = CaregiverContract::where('caregiver_id', $caregiverId)
            ->where('id', $contractId)
            ->first();

        if (!$contract) {
            return $this->error('Contrato nao encontrado', 404);
        }

        if (!$contract->is_signed) {
            return $this->error('Contrato precisa ser assinado antes de ativar', 400);
        }

        $contract->update([
            'status_id' => DomainContractStatus::ACTIVE,
        ]);

        return $this->success(
            $contract->fresh(['status']),
            'Contrato ativado com sucesso'
        );
    }

    /**
     * Encerra contrato.
     */
    public function close(Request $request, int $caregiverId, int $contractId): JsonResponse
    {
        $contract = CaregiverContract::where('caregiver_id', $caregiverId)
            ->where('id', $contractId)
            ->first();

        if (!$contract) {
            return $this->error('Contrato nao encontrado', 404);
        }

        $contract->update([
            'status_id' => DomainContractStatus::CLOSED,
        ]);

        return $this->success(
            $contract->fresh(['status']),
            'Contrato encerrado'
        );
    }

    /**
     * Envia contrato por email/WhatsApp.
     */
    public function send(Request $request, int $caregiverId, int $contractId): JsonResponse
    {
        $contract = CaregiverContract::where('caregiver_id', $caregiverId)
            ->where('id', $contractId)
            ->with('caregiver')
            ->first();

        if (!$contract) {
            return $this->error('Contrato nao encontrado', 404);
        }

        $channel = $request->get('channel', 'whatsapp'); // whatsapp ou email

        $result = $this->contractService->sendContract($contract, $channel);

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(null, 'Contrato enviado com sucesso');
    }
}
