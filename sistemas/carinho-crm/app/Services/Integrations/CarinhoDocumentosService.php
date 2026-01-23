<?php

namespace App\Services\Integrations;

/**
 * Integração com Carinho Documentos/LGPD (documentos.carinho.com.vc)
 * Gestão de contratos, consentimentos e conformidade LGPD
 */
class CarinhoDocumentosService extends BaseInternalService
{
    protected string $serviceName = 'carinho-documentos';
    protected int $timeout = 15; // Timeout maior para upload de documentos

    public function isEnabled(): bool
    {
        return config('integrations.internal.documentos.enabled', true) 
            && !empty($this->apiKey);
    }

    /**
     * Cria novo contrato para assinatura digital
     */
    public function createContract(int $contractId, array $contractData): ?array
    {
        return $this->post('contracts', [
            'contract_id' => $contractId,
            'client_id' => $contractData['client_id'],
            'client_name' => $contractData['client_name'],
            'client_email' => $contractData['client_email'] ?? null,
            'client_phone' => $contractData['client_phone'],
            'template' => $contractData['template'] ?? 'service_agreement',
            'variables' => $contractData['variables'] ?? [],
            'expires_in_days' => $contractData['expires_in_days'] ?? 7,
        ]);
    }

    /**
     * Gera link de assinatura digital
     */
    public function generateSignatureLink(int $contractId): ?array
    {
        return $this->post("contracts/{$contractId}/signature-link");
    }

    /**
     * Verifica status de assinatura
     */
    public function checkSignatureStatus(int $contractId): ?array
    {
        return $this->get("contracts/{$contractId}/signature-status");
    }

    /**
     * Obtém documento assinado (PDF)
     */
    public function getSignedDocument(int $contractId): ?array
    {
        return $this->get("contracts/{$contractId}/signed-document");
    }

    /**
     * Registra consentimento LGPD
     */
    public function registerConsent(int $clientId, array $consentData): ?array
    {
        return $this->post('consents', [
            'client_id' => $clientId,
            'consent_type' => $consentData['consent_type'],
            'source' => $consentData['source'],
            'ip_address' => $consentData['ip_address'] ?? null,
            'user_agent' => $consentData['user_agent'] ?? null,
            'granted_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Revoga consentimento
     */
    public function revokeConsent(int $clientId, string $consentType): ?array
    {
        return $this->delete("consents/{$clientId}/{$consentType}");
    }

    /**
     * Obtém consentimentos do cliente
     */
    public function getClientConsents(int $clientId): ?array
    {
        return $this->get("clients/{$clientId}/consents");
    }

    /**
     * Solicita exportação de dados (LGPD - portabilidade)
     */
    public function requestDataExport(int $clientId): ?array
    {
        return $this->post("clients/{$clientId}/data-export-request");
    }

    /**
     * Solicita exclusão de dados (LGPD - esquecimento)
     */
    public function requestDataDeletion(int $clientId, string $reason): ?array
    {
        return $this->post("clients/{$clientId}/deletion-request", [
            'reason' => $reason,
            'requested_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra auditoria de acesso aos dados
     */
    public function logDataAccess(int $clientId, string $accessType, int $userId): ?array
    {
        return $this->post('audit/data-access', [
            'client_id' => $clientId,
            'access_type' => $accessType,
            'user_id' => $userId,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Armazena documento do cliente
     */
    public function storeDocument(int $clientId, string $documentType, string $documentUrl, array $metadata = []): ?array
    {
        return $this->post("clients/{$clientId}/documents", [
            'type' => $documentType,
            'url' => $documentUrl,
            'metadata' => $metadata,
            'uploaded_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Lista documentos do cliente
     */
    public function getClientDocuments(int $clientId): ?array
    {
        return $this->get("clients/{$clientId}/documents");
    }
}
