<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DomainSignerType;
use App\Services\ContractService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(
        private ContractService $contractService,
        private NotificationService $notificationService
    ) {}

    /**
     * Lista contratos.
     */
    public function index(Request $request): JsonResponse
    {
        // Implementacao de listagem com filtros
        return $this->success([]);
    }

    /**
     * Cria novo contrato.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:contrato_cliente,contrato_cuidador',
            'owner_type' => 'required|string|in:client,caregiver',
            'owner_id' => 'required|integer',
            'variables' => 'required|array',
            'notify' => 'nullable|boolean',
            'phone' => 'required_if:notify,true|string',
            'email' => 'nullable|email',
            'recipient_name' => 'required|string',
        ]);

        $result = match ($validated['type']) {
            'contrato_cliente' => $this->contractService->createClientContract(
                $validated['owner_id'],
                $validated['variables']
            ),
            'contrato_cuidador' => $this->contractService->createCaregiverContract(
                $validated['owner_id'],
                $validated['variables']
            ),
            default => null,
        };

        if (!$result) {
            return $this->error('Falha ao criar contrato');
        }

        // Envia notificacao se solicitado
        if ($validated['notify'] ?? false) {
            $this->notificationService->notifyContractReady(
                $validated['phone'] ?? '',
                $validated['email'] ?? '',
                $validated['recipient_name'],
                $result['signature_url']
            );
        }

        return $this->created($result);
    }

    /**
     * Exibe contrato.
     */
    public function show(int $id): JsonResponse
    {
        $status = $this->contractService->getStatus($id);

        if (!$status) {
            return $this->notFound('Contrato nao encontrado');
        }

        return $this->success($status);
    }

    /**
     * Atualiza contrato.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Implementacao de atualizacao
        return $this->success([]);
    }

    /**
     * Assina contrato.
     */
    public function sign(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'signer_type' => 'required|string|in:client,caregiver,company',
            'signer_id' => 'required|integer',
            'method' => 'required|string|in:otp,click',
            'otp' => 'required_if:method,otp|string|size:6',
            'signature_token' => 'required|string',
        ]);

        $signerTypeId = match ($validated['signer_type']) {
            'client' => DomainSignerType::CLIENT,
            'caregiver' => DomainSignerType::CAREGIVER,
            'company' => DomainSignerType::COMPANY,
            default => DomainSignerType::CLIENT,
        };

        if ($validated['method'] === 'otp') {
            $result = $this->contractService->verifyOtpAndSign(
                $validated['signature_token'],
                $validated['otp'],
                $signerTypeId,
                $validated['signer_id'],
                $request->ip()
            );
        } else {
            $result = $this->contractService->signWithClick(
                $validated['signature_token'],
                $signerTypeId,
                $validated['signer_id'],
                $request->ip()
            );
        }

        if (!$result['ok']) {
            return $this->error($result['error'] ?? 'Falha na assinatura');
        }

        return $this->success($result);
    }

    /**
     * Gera URL de assinatura.
     */
    public function signatureUrl(int $id): JsonResponse
    {
        // Gera nova URL de assinatura
        $result = $this->contractService->sendSignatureLink($id, '', '');

        return $this->success($result);
    }

    /**
     * Obtem status do contrato.
     */
    public function status(int $id): JsonResponse
    {
        $status = $this->contractService->getStatus($id);

        if (!$status) {
            return $this->notFound('Contrato nao encontrado');
        }

        return $this->success($status);
    }

    /**
     * Download do contrato.
     */
    public function download(int $id): mixed
    {
        return $this->htmlContractResponse($id, 'contrato-'.$id.'.html');
    }

    /**
     * PDF nativo nao esta no vendor. Entrega HTML para impressao/salvar.
     */
    public function pdf(int $id): mixed
    {
        return $this->htmlContractResponse($id, 'contrato-'.$id.'.html');
    }

    private function htmlContractResponse(int $id, string $filename): mixed
    {
        $document = Document::query()->with('template')->find($id);

        if (!$document) {
            return $this->error('Contrato nao encontrado', 404);
        }

        $html = $document->template
            ? $document->template->render([
                'data_atualizacao' => now()->format('d/m/Y'),
                'document_id' => $document->id,
            ])
            : '<!DOCTYPE html><html lang="pt-BR"><body><p>Contrato #'.e($document->id).' sem template.</p></body></html>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Document-Format' => 'html',
        ]);
    }

    /**
     * Lista contratos de cliente.
     */
    public function byClient(int $clientId): JsonResponse
    {
        $contracts = $this->contractService->listByClient($clientId);

        return $this->success($contracts);
    }

    /**
     * Lista contratos de cuidador.
     */
    public function byCaregiver(int $caregiverId): JsonResponse
    {
        $contracts = $this->contractService->listByCaregiver($caregiverId);

        return $this->success($contracts);
    }

    /**
     * Exibe contrato publico por token.
     */
    public function showPublic(string $token): JsonResponse
    {
        $document = $this->contractService->getBySignatureToken($token);

        if (!$document) {
            return $this->error('Token invalido ou expirado', 401);
        }

        return $this->success([
            'document_id' => $document->id,
            'type' => $document->template?->docType?->label,
            'status' => $document->status->code,
            'is_signed' => $document->isSigned(),
        ]);
    }

    /**
     * Assina contrato publico.
     */
    public function signPublic(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'signer_type' => 'required|string|in:client,caregiver',
            'signer_id' => 'required|integer',
            'method' => 'required|string|in:otp,click',
            'otp' => 'required_if:method,otp|string|size:6',
        ]);

        $signerTypeId = match ($validated['signer_type']) {
            'client' => DomainSignerType::CLIENT,
            'caregiver' => DomainSignerType::CAREGIVER,
            default => DomainSignerType::CLIENT,
        };

        if ($validated['method'] === 'otp') {
            $result = $this->contractService->verifyOtpAndSign(
                $token,
                $validated['otp'],
                $signerTypeId,
                $validated['signer_id'],
                $request->ip()
            );
        } else {
            $result = $this->contractService->signWithClick(
                $token,
                $signerTypeId,
                $validated['signer_id'],
                $request->ip()
            );
        }

        if (!$result['ok']) {
            return $this->error($result['error'] ?? 'Falha na assinatura');
        }

        return $this->success($result);
    }
}
