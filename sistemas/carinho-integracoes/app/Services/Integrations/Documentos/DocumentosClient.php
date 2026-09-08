<?php

namespace App\Services\Integrations\Documentos;

use App\Services\Integrations\BaseClient;

/**
 * Cliente de Documentos/LGPD. Rotas reais: /api/documents, /api/contracts, /api/consents (sem /v1).
 */
class DocumentosClient extends BaseClient
{
    protected string $configKey = 'documentos';

    public function createContract(array $data): array
    {
        return $this->post('/api/contracts', $data);
    }

    public function getContract(int $contractId): array
    {
        return $this->get("/api/contracts/{$contractId}");
    }

    public function generateSignatureLink(int $contractId): array
    {
        return $this->get("/api/contracts/{$contractId}/signature-url");
    }

    public function checkSignatureStatus(int $contractId): array
    {
        return $this->get("/api/contracts/{$contractId}/status");
    }

    public function recordSignature(int $contractId, array $data): array
    {
        return $this->post("/api/contracts/{$contractId}/sign", [
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
            'signature_hash' => $data['signature_hash'] ?? null,
            'signed_at' => $data['signed_at'] ?? now()->toIso8601String(),
        ]);
    }

    public function registerConsent(array $data): array
    {
        return $this->post('/api/consents', [
            'subject_id' => $data['subject_id'],
            'subject_type' => $data['subject_type'],
            'purpose' => $data['purpose'],
            'legal_basis' => $data['legal_basis'],
            'granted' => $data['granted'],
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    public function revokeConsent(int $consentId): array
    {
        return $this->delete("/api/consents/{$consentId}");
    }

    public function getSubjectConsents(int $subjectId, string $subjectType): array
    {
        return $this->get("/api/consents/subject/{$subjectType}/{$subjectId}");
    }

    public function hasValidConsent(int $subjectId, string $subjectType, string $purpose): array
    {
        return $this->get("/api/consents/check/{$subjectType}/{$subjectId}/{$purpose}");
    }

    public function uploadDocument(array $data): array
    {
        return $this->post('/api/documents', $data);
    }

    public function getDocument(int $documentId): array
    {
        return $this->get("/api/documents/{$documentId}");
    }

    public function getTemporaryUrl(int $documentId, int $expiresInMinutes = 15): array
    {
        return $this->get("/api/documents/{$documentId}/signed-url", [
            'expires_in' => $expiresInMinutes,
        ]);
    }

    public function getSubjectDocuments(int $subjectId, string $subjectType): array
    {
        return $this->get("/api/documents/owner/{$subjectType}/{$subjectId}");
    }

    public function requestDataExport(int $subjectId, string $subjectType): array
    {
        return $this->post('/api/data-requests/export', [
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
        ]);
    }

    public function requestDataDeletion(int $subjectId, string $subjectType, string $reason): array
    {
        return $this->post('/api/data-requests/delete', [
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
            'reason' => $reason,
        ]);
    }

    public function getDataRequestStatus(int $requestId): array
    {
        return $this->get("/api/data-requests/{$requestId}");
    }

    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->unsupported("webhook de eventos documentos ({$eventType})");
    }
}
