<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\CaregiverDocument;
use App\Models\DomainDocumentStatus;
use App\Models\DomainDocumentType;
use App\Integrations\Documentos\DocumentosClient;
use App\Jobs\SendCaregiverNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentValidationService
{
    public function __construct(
        private DocumentosClient $documentosClient
    ) {}

    /**
     * Faz upload de documento para o storage seguro.
     */
    public function uploadDocument(Caregiver $caregiver, UploadedFile $file): string
    {
        // Gera nome unico para o arquivo
        $extension = $file->getClientOriginalExtension();
        $filename = sprintf(
            '%d_%s_%s.%s',
            $caregiver->id,
            now()->format('YmdHis'),
            bin2hex(random_bytes(8)),
            $extension
        );

        $path = "caregivers/{$caregiver->id}/documents/{$filename}";

        // Tenta enviar para o sistema de documentos externo
        $result = $this->documentosClient->upload($file, [
            'caregiver_id' => $caregiver->id,
            'filename' => $filename,
        ]);

        if ($result['ok']) {
            return $result['body']['url'] ?? $path;
        }

        // Fallback: armazena localmente
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        Log::warning('Upload para sistema de documentos falhou, armazenado localmente', [
            'caregiver_id' => $caregiver->id,
            'path' => $path,
        ]);

        return $path;
    }

    /**
     * Valida documento automaticamente.
     */
    public function validateDocument(CaregiverDocument $document): array
    {
        // Envia para validacao no sistema de documentos
        $result = $this->documentosClient->validate([
            'document_id' => $document->id,
            'file_url' => $document->file_url,
            'doc_type' => $document->docType?->code,
        ]);

        if (!$result['ok']) {
            Log::warning('Falha na validacao automatica de documento', [
                'document_id' => $document->id,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => 'Falha na validacao automatica',
                'needs_manual_review' => true,
            ];
        }

        $validationResult = $result['body'] ?? [];

        // Se passou na validacao automatica
        if ($validationResult['valid'] ?? false) {
            $document->update([
                'status_id' => DomainDocumentStatus::VERIFIED,
                'verified_at' => now(),
            ]);

            // Notifica cuidador
            SendCaregiverNotification::dispatch(
                $document->caregiver,
                'document_approved',
                ['doc_type' => $document->docType?->label]
            );

            return [
                'success' => true,
                'message' => 'Documento validado automaticamente',
            ];
        }

        // Se rejeitado automaticamente (ex: documento ilegivel)
        if (($validationResult['rejected'] ?? false) && ($validationResult['auto_reject'] ?? false)) {
            $document->update([
                'status_id' => DomainDocumentStatus::REJECTED,
            ]);

            $this->notifyDocumentRejected($document, $validationResult['reason'] ?? null);

            return [
                'success' => false,
                'message' => $validationResult['reason'] ?? 'Documento rejeitado',
            ];
        }

        // Precisa de revisao manual
        return [
            'success' => false,
            'message' => 'Documento requer revisao manual',
            'needs_manual_review' => true,
        ];
    }

    /**
     * Retorna lista de documentos obrigatorios faltantes.
     */
    public function getMissingRequiredDocuments(Caregiver $caregiver): array
    {
        $requiredCodes = DomainDocumentType::required();

        $verifiedCodes = $caregiver->documents()
            ->verified()
            ->with('docType')
            ->get()
            ->pluck('docType.code')
            ->toArray();

        $missing = array_diff($requiredCodes, $verifiedCodes);

        return array_values($missing);
    }

    /**
     * Verifica se todos os documentos obrigatorios estao aprovados.
     */
    public function hasAllRequiredDocuments(Caregiver $caregiver): bool
    {
        return empty($this->getMissingRequiredDocuments($caregiver));
    }

    /**
     * Notifica cuidador sobre documento rejeitado.
     */
    public function notifyDocumentRejected(CaregiverDocument $document, ?string $reason = null): void
    {
        SendCaregiverNotification::dispatch(
            $document->caregiver,
            'document_rejected',
            [
                'doc_type' => $document->docType?->label,
                'reason' => $reason ?? 'Documento nao atende aos criterios minimos',
            ]
        );

        Log::info('Notificacao de documento rejeitado enviada', [
            'document_id' => $document->id,
            'caregiver_id' => $document->caregiver_id,
        ]);
    }

    /**
     * Gera URL assinada para visualizacao do documento.
     */
    public function getSignedUrl(CaregiverDocument $document, int $expiresMinutes = 60): ?string
    {
        $result = $this->documentosClient->getSignedUrl($document->file_url, $expiresMinutes);

        if ($result['ok']) {
            return $result['body']['url'] ?? null;
        }

        // Fallback para storage local
        if (Storage::disk('local')->exists($document->file_url)) {
            return Storage::disk('local')->temporaryUrl(
                $document->file_url,
                now()->addMinutes($expiresMinutes)
            );
        }

        return null;
    }
}
