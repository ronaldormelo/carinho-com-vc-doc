<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\Document;
use App\Models\Signature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de assinaturas digitais.
 */
class SignatureService
{
    /**
     * Obtem assinatura por ID.
     */
    public function find(int $signatureId): ?Signature
    {
        return Signature::with(['document', 'signerType', 'method'])
            ->find($signatureId);
    }

    /**
     * Lista assinaturas de um documento.
     */
    public function listByDocument(int $documentId): array
    {
        return Signature::findByDocument($documentId)
            ->map(fn ($sig) => [
                'id' => $sig->id,
                'document_id' => $sig->document_id,
                'signer_type' => $sig->signerType->code,
                'signer_id' => $sig->signer_id,
                'signed_at' => $sig->signed_at->toIso8601String(),
                'method' => $sig->method->code,
                'verification_hash' => $sig->generateVerificationHash(),
            ])
            ->toArray();
    }

    /**
     * Verifica assinatura.
     */
    public function verify(int $signatureId, string $hash): array
    {
        try {
            $signature = Signature::with(['document', 'signerType', 'method'])
                ->findOrFail($signatureId);

            $isValid = $signature->verify($hash);

            Log::info('Signature verification', [
                'signature_id' => $signatureId,
                'is_valid' => $isValid,
            ]);

            return [
                'ok' => true,
                'is_valid' => $isValid,
                'signature' => [
                    'id' => $signature->id,
                    'document_id' => $signature->document_id,
                    'signer_type' => $signature->signerType->code,
                    'signed_at' => $signature->signed_at->toIso8601String(),
                    'method' => $signature->method->code,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Signature verification failed', [
                'signature_id' => $signatureId,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'is_valid' => false,
                'error' => 'Assinatura nao encontrada',
            ];
        }
    }

    /**
     * Obtem certificado de assinatura.
     */
    public function getCertificate(int $signatureId): ?array
    {
        $signature = Signature::with(['document.template.docType', 'signerType', 'method'])
            ->find($signatureId);

        if (!$signature) {
            return null;
        }

        return [
            'certificate_id' => $signature->id,
            'document' => [
                'id' => $signature->document->id,
                'type' => $signature->document->template?->docType?->label,
                'created_at' => $signature->document->created_at->toIso8601String(),
            ],
            'signer' => [
                'type' => $signature->signerType->label,
                'id' => $signature->signer_id,
            ],
            'signature' => [
                'signed_at' => $signature->signed_at->toIso8601String(),
                'method' => $signature->method->label,
                'ip_address' => $this->maskIp($signature->ip_address),
            ],
            'verification' => [
                'hash' => $signature->generateVerificationHash(),
                'algorithm' => 'HMAC-SHA256',
            ],
            'issuer' => [
                'name' => config('branding.name', 'Carinho com Voce'),
                'domain' => config('branding.domain', 'carinho.com.vc'),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtem estatisticas de assinaturas.
     */
    public function getStatistics(string $period = 'month'): array
    {
        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $total = Signature::where('signed_at', '>=', $startDate)->count();

        $byMethod = Signature::where('signed_at', '>=', $startDate)
            ->select('method_id', DB::raw('count(*) as count'))
            ->groupBy('method_id')
            ->with('method')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->method->code => $item->count])
            ->toArray();

        $bySignerType = Signature::where('signed_at', '>=', $startDate)
            ->select('signer_type_id', DB::raw('count(*) as count'))
            ->groupBy('signer_type_id')
            ->with('signerType')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->signerType->code => $item->count])
            ->toArray();

        return [
            'period' => $period,
            'start_date' => $startDate->toIso8601String(),
            'total' => $total,
            'by_method' => $byMethod,
            'by_signer_type' => $bySignerType,
        ];
    }

    /**
     * Mascara IP para exibicao.
     */
    private function maskIp(string $ip): string
    {
        $parts = explode('.', $ip);

        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.***.' . $parts[3];
        }

        // IPv6 ou formato desconhecido
        return substr($ip, 0, 8) . '...';
    }
}
