<?php

namespace App\Services\Integrations\Documentos;

use App\Services\Integrations\BaseClient;

/**
 * Cliente para integracao com o sistema de Documentos e LGPD (documentos.carinho.com.vc).
 *
 * Responsavel por:
 * - Gerenciamento de contratos digitais
 * - Consentimentos LGPD
 * - Armazenamento seguro de documentos
 */
class DocumentosClient extends BaseClient
{
    protected string $configKey = 'documentos';

    /*
    |--------------------------------------------------------------------------
    | Contratos
    |--------------------------------------------------------------------------
    */

    /**
     * Cria contrato digital.
     */
    public function createContract(array $data): array
    {
        return $this->post('/api/v1/contracts', $data);
    }

    /**
     * Busca contrato por ID.
     */
    public function getContract(int $contractId): array
    {
        return $this->get("/api/v1/contracts/{$contractId}");
    }

    /**
     * Gera link para assinatura digital.
     */
    public function generateSignatureLink(int $contractId): array
    {
        return $this->post("/api/v1/contracts/{$contractId}/signature-link");
    }

    /**
     * Verifica status de assinatura.
     */
    public function checkSignatureStatus(int $contractId): array
    {
        return $this->get("/api/v1/contracts/{$contractId}/signature-status");
    }

    /**
     * Registra assinatura.
     */
    public function recordSignature(int $contractId, array $data): array
    {
        return $this->post("/api/v1/contracts/{$contractId}/sign", [
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
            'signature_hash' => $data['signature_hash'] ?? null,
            'signed_at' => $data['signed_at'] ?? now()->toIso8601String(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Consentimentos LGPD
    |--------------------------------------------------------------------------
    */

    /**
     * Registra consentimento.
     */
    public function registerConsent(array $data): array
    {
        return $this->post('/api/v1/consents', [
            'subject_id' => $data['subject_id'],
            'subject_type' => $data['subject_type'], // client, caregiver
            'purpose' => $data['purpose'],
            'legal_basis' => $data['legal_basis'],
            'granted' => $data['granted'],
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    /**
     * Revoga consentimento.
     */
    public function revokeConsent(int $consentId): array
    {
        return $this->post("/api/v1/consents/{$consentId}/revoke");
    }

    /**
     * Busca consentimentos de um sujeito.
     */
    public function getSubjectConsents(int $subjectId, string $subjectType): array
    {
        return $this->get('/api/v1/consents', [
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
        ]);
    }

    /**
     * Verifica se possui consentimento valido.
     */
    public function hasValidConsent(int $subjectId, string $subjectType, string $purpose): array
    {
        return $this->get('/api/v1/consents/check', [
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
            'purpose' => $purpose,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Documentos
    |--------------------------------------------------------------------------
    */

    /**
     * Faz upload de documento.
     */
    public function uploadDocument(array $data): array
    {
        return $this->post('/api/v1/documents', $data);
    }

    /**
     * Busca documento por ID.
     */
    public function getDocument(int $documentId): array
    {
        return $this->get("/api/v1/documents/{$documentId}");
    }

    /**
     * Gera URL temporaria para download.
     */
    public function getTemporaryUrl(int $documentId, int $expiresInMinutes = 15): array
    {
        return $this->post("/api/v1/documents/{$documentId}/temporary-url", [
            'expires_in' => $expiresInMinutes,
        ]);
    }

    /**
     * Lista documentos de um sujeito.
     */
    public function getSubjectDocuments(int $subjectId, string $subjectType): array
    {
        return $this->get('/api/v1/documents', [
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Direitos do Titular (LGPD)
    |--------------------------------------------------------------------------
    */

    /**
     * Solicita exportacao de dados (portabilidade).
     */
    public function requestDataExport(int $subjectId, string $subjectType): array
    {
        return $this->post('/api/v1/data-requests/export', [
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
        ]);
    }

    /**
     * Solicita exclusao de dados.
     */
    public function requestDataDeletion(int $subjectId, string $subjectType, string $reason): array
    {
        return $this->post('/api/v1/data-requests/deletion', [
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
            'reason' => $reason,
        ]);
    }

    /**
     * Busca status de solicitacao.
     */
    public function getDataRequestStatus(int $requestId): array
    {
        return $this->get("/api/v1/data-requests/{$requestId}");
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Dispara evento para Documentos.
     */
    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->post('/api/v1/webhooks/events', [
            'event_type' => $eventType,
            'payload' => $payload,
            'source' => 'integracoes',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
