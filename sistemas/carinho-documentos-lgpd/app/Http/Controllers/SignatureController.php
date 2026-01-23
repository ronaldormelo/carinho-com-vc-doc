<?php

namespace App\Http\Controllers;

use App\Services\ContractService;
use App\Services\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    public function __construct(
        private SignatureService $signatureService,
        private ContractService $contractService
    ) {}

    /**
     * Lista assinaturas.
     */
    public function index(Request $request): JsonResponse
    {
        // Implementacao de listagem com filtros
        return $this->success([]);
    }

    /**
     * Cria assinatura.
     */
    public function store(Request $request): JsonResponse
    {
        // Assinatura eh criada atraves do ContractController
        return $this->error('Use /contracts/{id}/sign');
    }

    /**
     * Exibe assinatura.
     */
    public function show(int $id): JsonResponse
    {
        $signature = $this->signatureService->find($id);

        if (!$signature) {
            return $this->notFound('Assinatura nao encontrada');
        }

        return $this->success([
            'id' => $signature->id,
            'document_id' => $signature->document_id,
            'signer_type' => $signature->signerType->code,
            'signer_id' => $signature->signer_id,
            'signed_at' => $signature->signed_at->toIso8601String(),
            'method' => $signature->method->code,
        ]);
    }

    /**
     * Lista assinaturas por documento.
     */
    public function byDocument(int $documentId): JsonResponse
    {
        $signatures = $this->signatureService->listByDocument($documentId);

        return $this->success($signatures);
    }

    /**
     * Verifica assinatura.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'hash' => 'required|string',
        ]);

        $result = $this->signatureService->verify($id, $validated['hash']);

        return $this->success($result);
    }

    /**
     * Envia OTP para assinatura.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signature_token' => 'required|string',
            'phone' => 'required|string',
        ]);

        $result = $this->contractService->sendSignatureOtp(
            $validated['signature_token'],
            $validated['phone']
        );

        if (!$result['ok']) {
            return $this->error($result['error'] ?? 'Falha ao enviar codigo');
        }

        return $this->success($result);
    }

    /**
     * Verifica OTP.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        // Verificacao de OTP eh feita no ContractController.sign
        return $this->error('Use /contracts/{token}/sign com method=otp');
    }
}
