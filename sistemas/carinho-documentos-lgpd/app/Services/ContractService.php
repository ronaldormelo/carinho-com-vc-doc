<?php

namespace App\Services;

use App\Integrations\Storage\S3StorageClient;
use App\Integrations\WhatsApp\ZApiClient;
use App\Models\AccessLog;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentVersion;
use App\Models\DomainDocType;
use App\Models\DomainDocumentStatus;
use App\Models\DomainOwnerType;
use App\Models\DomainSignatureMethod;
use App\Models\DomainSignerType;
use App\Models\Signature;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service para gerenciamento de contratos.
 */
class ContractService
{
    private const SIGNATURE_TOKEN_CACHE_PREFIX = 'contract:signature:token:';
    private const OTP_CACHE_PREFIX = 'contract:otp:';

    public function __construct(
        private S3StorageClient $storage,
        private ZApiClient $whatsApp
    ) {}

    /**
     * Cria contrato de cliente.
     */
    public function createClientContract(int $clientId, array $data): ?array
    {
        return $this->createContract(
            DomainDocType::CONTRATO_CLIENTE,
            DomainOwnerType::CLIENT,
            $clientId,
            $data
        );
    }

    /**
     * Cria contrato de cuidador.
     */
    public function createCaregiverContract(int $caregiverId, array $data): ?array
    {
        return $this->createContract(
            DomainDocType::CONTRATO_CUIDADOR,
            DomainOwnerType::CAREGIVER,
            $caregiverId,
            $data
        );
    }

    /**
     * Cria contrato.
     */
    public function createContract(
        int $docTypeId,
        int $ownerTypeId,
        int $ownerId,
        array $variables
    ): ?array {
        try {
            return DB::transaction(function () use ($docTypeId, $ownerTypeId, $ownerId, $variables) {
                // Obtem template ativo
                $template = DocumentTemplate::getActiveByType($docTypeId);
                if (!$template) {
                    throw new \Exception('Template de contrato nao encontrado');
                }

                // Adiciona data de assinatura
                $variables['data_assinatura'] = now()->format('d/m/Y');

                // Cria documento
                $document = Document::create([
                    'owner_type_id' => $ownerTypeId,
                    'owner_id' => $ownerId,
                    'template_id' => $template->id,
                    'status_id' => DomainDocumentStatus::DRAFT,
                ]);

                // Renderiza conteudo
                $content = $template->render($variables);

                // Faz upload
                $path = $this->buildContractPath($ownerTypeId, $ownerId, $document->id);
                $uploadResult = $this->storage->upload($content, $path . '.html', [
                    'document_id' => (string) $document->id,
                    'doc_type' => DomainDocType::CODES[$docTypeId],
                    'mime_type' => 'text/html',
                ]);

                if (!$uploadResult['ok']) {
                    throw new \Exception('Falha no upload do contrato');
                }

                // Cria versao
                DocumentVersion::create([
                    'document_id' => $document->id,
                    'version' => '1.0',
                    'file_url' => $path . '.html',
                    'checksum' => $uploadResult['checksum'],
                    'created_at' => now(),
                ]);

                // Gera token de assinatura
                $signatureToken = $this->generateSignatureToken($document->id);

                Log::info('Contract created', [
                    'document_id' => $document->id,
                    'doc_type' => $docTypeId,
                    'owner_type' => $ownerTypeId,
                    'owner_id' => $ownerId,
                ]);

                return [
                    'document_id' => $document->id,
                    'signature_token' => $signatureToken,
                    'signature_url' => $this->buildSignatureUrl($signatureToken),
                    'expires_at' => now()->addMinutes(
                        config('documentos.signed_urls.signature_expiration', 4320)
                    )->toIso8601String(),
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create contract', [
                'error' => $e->getMessage(),
                'doc_type' => $docTypeId,
                'owner_type' => $ownerTypeId,
            ]);

            return null;
        }
    }

    /**
     * Obtem contrato por token de assinatura.
     */
    public function getBySignatureToken(string $token): ?Document
    {
        $documentId = Cache::get(self::SIGNATURE_TOKEN_CACHE_PREFIX . $token);

        if (!$documentId) {
            return null;
        }

        return Document::with(['template.docType', 'status', 'signatures'])
            ->find($documentId);
    }

    /**
     * Envia OTP para assinatura.
     */
    public function sendSignatureOtp(string $signatureToken, string $phone): array
    {
        try {
            $document = $this->getBySignatureToken($signatureToken);

            if (!$document) {
                return ['ok' => false, 'error' => 'Token invalido'];
            }

            if ($document->isSigned()) {
                return ['ok' => false, 'error' => 'Contrato ja assinado'];
            }

            // Gera OTP
            $otp = $this->generateOtp();
            $otpKey = self::OTP_CACHE_PREFIX . $signatureToken;

            // Salva OTP no cache
            Cache::put($otpKey, [
                'code' => $otp,
                'phone' => $phone,
                'attempts' => 0,
            ], config('documentos.signature.otp.expiration_minutes', 10) * 60);

            // Envia via WhatsApp
            $result = $this->whatsApp->sendOtpCode($phone, $otp);

            if (!$result['ok']) {
                return ['ok' => false, 'error' => 'Falha ao enviar codigo'];
            }

            return [
                'ok' => true,
                'message' => 'Codigo enviado',
                'expires_in_minutes' => config('documentos.signature.otp.expiration_minutes', 10),
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP', [
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'Erro interno'];
        }
    }

    /**
     * Verifica OTP e assina contrato.
     */
    public function verifyOtpAndSign(
        string $signatureToken,
        string $otp,
        int $signerTypeId,
        int $signerId,
        string $ipAddress
    ): array {
        try {
            $document = $this->getBySignatureToken($signatureToken);

            if (!$document) {
                return ['ok' => false, 'error' => 'Token invalido'];
            }

            if ($document->isSigned()) {
                return ['ok' => false, 'error' => 'Contrato ja assinado'];
            }

            // Valida OTP
            $otpKey = self::OTP_CACHE_PREFIX . $signatureToken;
            $otpData = Cache::get($otpKey);

            if (!$otpData) {
                return ['ok' => false, 'error' => 'Codigo expirado'];
            }

            $maxAttempts = config('documentos.signature.otp.max_attempts', 3);
            if ($otpData['attempts'] >= $maxAttempts) {
                Cache::forget($otpKey);

                return ['ok' => false, 'error' => 'Tentativas excedidas'];
            }

            if ($otpData['code'] !== $otp) {
                $otpData['attempts']++;
                Cache::put($otpKey, $otpData, config('documentos.signature.otp.expiration_minutes', 10) * 60);

                return ['ok' => false, 'error' => 'Codigo invalido'];
            }

            // Remove OTP do cache
            Cache::forget($otpKey);

            // Registra assinatura
            return $this->sign(
                $document->id,
                $signerTypeId,
                $signerId,
                DomainSignatureMethod::OTP,
                $ipAddress
            );
        } catch (\Throwable $e) {
            Log::error('Failed to verify OTP', [
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'Erro interno'];
        }
    }

    /**
     * Assina contrato com clique.
     */
    public function signWithClick(
        string $signatureToken,
        int $signerTypeId,
        int $signerId,
        string $ipAddress
    ): array {
        $document = $this->getBySignatureToken($signatureToken);

        if (!$document) {
            return ['ok' => false, 'error' => 'Token invalido'];
        }

        if ($document->isSigned()) {
            return ['ok' => false, 'error' => 'Contrato ja assinado'];
        }

        return $this->sign(
            $document->id,
            $signerTypeId,
            $signerId,
            DomainSignatureMethod::CLICK,
            $ipAddress
        );
    }

    /**
     * Registra assinatura.
     */
    public function sign(
        int $documentId,
        int $signerTypeId,
        int $signerId,
        int $methodId,
        string $ipAddress
    ): array {
        try {
            return DB::transaction(function () use ($documentId, $signerTypeId, $signerId, $methodId, $ipAddress) {
                $document = Document::findOrFail($documentId);

                // Cria assinatura
                $signature = Signature::create([
                    'document_id' => $documentId,
                    'signer_type_id' => $signerTypeId,
                    'signer_id' => $signerId,
                    'signed_at' => now(),
                    'method_id' => $methodId,
                    'ip_address' => $ipAddress,
                ]);

                // Atualiza status do documento
                $document->markAsSigned();

                // Registra log
                AccessLog::logSign($documentId, $signerId, $ipAddress);

                // Gera hash de verificacao
                $verificationHash = $signature->generateVerificationHash();

                Log::info('Contract signed', [
                    'document_id' => $documentId,
                    'signer_type' => $signerTypeId,
                    'signer_id' => $signerId,
                    'method' => $methodId,
                ]);

                return [
                    'ok' => true,
                    'signature_id' => $signature->id,
                    'signed_at' => $signature->signed_at->toIso8601String(),
                    'verification_hash' => $verificationHash,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Failed to sign contract', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'Erro ao assinar contrato'];
        }
    }

    /**
     * Obtem status do contrato.
     */
    public function getStatus(int $documentId): ?array
    {
        $document = Document::with(['status', 'signatures.method', 'signatures.signerType'])
            ->find($documentId);

        if (!$document) {
            return null;
        }

        return [
            'document_id' => $document->id,
            'status' => $document->status->code,
            'is_signed' => $document->isSigned(),
            'signatures' => $document->signatures->map(fn ($sig) => [
                'id' => $sig->id,
                'signer_type' => $sig->signerType->code,
                'signed_at' => $sig->signed_at->toIso8601String(),
                'method' => $sig->method->code,
            ])->toArray(),
            'created_at' => $document->created_at->toIso8601String(),
        ];
    }

    /**
     * Envia link de assinatura via WhatsApp.
     */
    public function sendSignatureLink(int $documentId, string $phone, string $recipientName): array
    {
        try {
            $document = Document::find($documentId);

            if (!$document) {
                return ['ok' => false, 'error' => 'Contrato nao encontrado'];
            }

            // Gera novo token se necessario
            $token = $this->generateSignatureToken($document->id);
            $url = $this->buildSignatureUrl($token);

            // Envia via WhatsApp
            $result = $this->whatsApp->sendContractLink($phone, $url, $recipientName);

            return [
                'ok' => $result['ok'],
                'error' => $result['error'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to send signature link', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'Erro interno'];
        }
    }

    /**
     * Lista contratos de cliente.
     */
    public function listByClient(int $clientId): array
    {
        return Document::forClient($clientId)
            ->whereHas('template.docType', fn ($q) => $q->where('code', 'like', 'contrato_%'))
            ->with(['status', 'template.docType'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Lista contratos de cuidador.
     */
    public function listByCaregiver(int $caregiverId): array
    {
        return Document::forCaregiver($caregiverId)
            ->whereHas('template.docType', fn ($q) => $q->where('code', 'like', 'contrato_%'))
            ->with(['status', 'template.docType'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Gera token de assinatura.
     */
    private function generateSignatureToken(int $documentId): string
    {
        $token = Str::random(64);

        Cache::put(
            self::SIGNATURE_TOKEN_CACHE_PREFIX . $token,
            $documentId,
            config('documentos.signed_urls.signature_expiration', 4320) * 60
        );

        return $token;
    }

    /**
     * Gera codigo OTP.
     */
    private function generateOtp(): string
    {
        $length = config('documentos.signature.otp.length', 6);

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Constroi URL de assinatura.
     */
    private function buildSignatureUrl(string $token): string
    {
        $domain = config('branding.subdomain', 'documentos.carinho.com.vc');

        return "https://{$domain}/assinar/{$token}";
    }

    /**
     * Constroi caminho do contrato no S3.
     */
    private function buildContractPath(int $ownerTypeId, int $ownerId, int $documentId): string
    {
        $prefix = match ($ownerTypeId) {
            DomainOwnerType::CLIENT => 'contracts/clients',
            DomainOwnerType::CAREGIVER => 'contracts/caregivers',
            default => 'contracts/other',
        };

        $date = now();

        return "{$prefix}/{$ownerId}/{$date->format('Y')}/{$date->format('m')}/contract_{$documentId}";
    }
}
