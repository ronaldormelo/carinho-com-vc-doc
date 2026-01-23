<?php

namespace App\Services;

use App\Integrations\Storage\S3StorageClient;
use App\Integrations\WhatsApp\ZApiClient;
use App\Models\AccessLog;
use App\Models\Consent;
use App\Models\DataRequest;
use App\Models\Document;
use App\Models\DomainConsentSubjectType;
use App\Models\DomainOwnerType;
use App\Models\DomainRequestStatus;
use App\Models\DomainRequestType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de solicitacoes LGPD.
 */
class LgpdService
{
    public function __construct(
        private S3StorageClient $storage,
        private ZApiClient $whatsApp,
        private ConsentService $consentService
    ) {}

    /**
     * Cria solicitacao de exportacao de dados.
     */
    public function requestDataExport(int $subjectTypeId, int $subjectId): ?DataRequest
    {
        try {
            $request = DataRequest::createExportRequest($subjectTypeId, $subjectId);

            Log::info('Data export requested', [
                'request_id' => $request->id,
                'subject_type' => $subjectTypeId,
                'subject_id' => $subjectId,
            ]);

            return $request;
        } catch (\Throwable $e) {
            Log::error('Failed to create export request', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cria solicitacao de exclusao de dados.
     */
    public function requestDataDeletion(int $subjectTypeId, int $subjectId): ?DataRequest
    {
        try {
            $request = DataRequest::createDeleteRequest($subjectTypeId, $subjectId);

            Log::info('Data deletion requested', [
                'request_id' => $request->id,
                'subject_type' => $subjectTypeId,
                'subject_id' => $subjectId,
            ]);

            return $request;
        } catch (\Throwable $e) {
            Log::error('Failed to create deletion request', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Processa solicitacao de exportacao.
     */
    public function processExportRequest(int $requestId): array
    {
        try {
            $request = DataRequest::findOrFail($requestId);

            if ($request->request_type_id !== DomainRequestType::EXPORT) {
                return ['ok' => false, 'error' => 'Tipo de solicitacao invalido'];
            }

            $request->markAsInProgress();

            // Coleta dados do titular
            $ownerTypeId = $this->mapSubjectToOwnerType($request->subject_type_id);
            $data = $this->collectSubjectData($ownerTypeId, $request->subject_id);

            // Gera arquivo JSON
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $filename = "export_{$request->subject_id}_" . now()->format('Ymd_His') . '.json';
            $path = "exports/{$request->subject_type_id}/{$request->subject_id}/{$filename}";

            // Faz upload
            $uploadResult = $this->storage->upload($jsonContent, $path, [
                'request_id' => (string) $requestId,
                'subject_type' => (string) $request->subject_type_id,
                'subject_id' => (string) $request->subject_id,
                'mime_type' => 'application/json',
            ]);

            if (!$uploadResult['ok']) {
                throw new \Exception('Falha no upload do arquivo');
            }

            // Gera URL assinada para download (valida por 7 dias)
            $urlResult = $this->storage->getSignedUrl($path, 10080);

            $request->markAsDone();

            Log::info('Export request processed', [
                'request_id' => $requestId,
                'path' => $path,
            ]);

            return [
                'ok' => true,
                'request_id' => $requestId,
                'download_url' => $urlResult['url'] ?? null,
                'expires_at' => $urlResult['expires_at'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to process export request', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Processa solicitacao de exclusao.
     */
    public function processDeleteRequest(int $requestId): array
    {
        try {
            $request = DataRequest::findOrFail($requestId);

            if ($request->request_type_id !== DomainRequestType::DELETE) {
                return ['ok' => false, 'error' => 'Tipo de solicitacao invalido'];
            }

            $request->markAsInProgress();

            return DB::transaction(function () use ($request) {
                $ownerTypeId = $this->mapSubjectToOwnerType($request->subject_type_id);

                // Exclui documentos do S3
                $documents = Document::where('owner_type_id', $ownerTypeId)
                    ->where('owner_id', $request->subject_id)
                    ->with('versions')
                    ->get();

                foreach ($documents as $document) {
                    foreach ($document->versions as $version) {
                        $this->storage->delete($version->file_url);
                    }
                    $document->delete();
                }

                // Revoga todos os consentimentos
                $this->consentService->revokeAll($request->subject_type_id, $request->subject_id);

                $request->markAsDone();

                Log::info('Delete request processed', [
                    'request_id' => $request->id,
                    'documents_deleted' => $documents->count(),
                ]);

                return [
                    'ok' => true,
                    'request_id' => $request->id,
                    'documents_deleted' => $documents->count(),
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Failed to process delete request', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rejeita solicitacao.
     */
    public function rejectRequest(int $requestId, string $reason): bool
    {
        try {
            $request = DataRequest::findOrFail($requestId);
            $request->markAsRejected();

            Log::info('Request rejected', [
                'request_id' => $requestId,
                'reason' => $reason,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to reject request', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Lista solicitacoes pendentes.
     */
    public function listPendingRequests(): array
    {
        return DataRequest::pending()
            ->with(['subjectType', 'requestType', 'status'])
            ->orderBy('requested_at')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'subject_type' => $r->subjectType->code,
                'subject_id' => $r->subject_id,
                'request_type' => $r->requestType->code,
                'status' => $r->status->code,
                'requested_at' => $r->requested_at->toIso8601String(),
                'days_until_deadline' => $r->daysUntilDeadline(),
                'is_overdue' => $r->isOverdue(),
            ])
            ->toArray();
    }

    /**
     * Lista solicitacoes vencidas.
     */
    public function listOverdueRequests(): array
    {
        return DataRequest::overdue()
            ->with(['subjectType', 'requestType', 'status'])
            ->orderBy('requested_at')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'subject_type' => $r->subjectType->code,
                'subject_id' => $r->subject_id,
                'request_type' => $r->requestType->code,
                'requested_at' => $r->requested_at->toIso8601String(),
                'days_overdue' => abs($r->daysUntilDeadline()),
            ])
            ->toArray();
    }

    /**
     * Obtem solicitacao por ID.
     */
    public function getRequest(int $requestId): ?array
    {
        $request = DataRequest::with(['subjectType', 'requestType', 'status'])
            ->find($requestId);

        if (!$request) {
            return null;
        }

        return [
            'id' => $request->id,
            'subject_type' => $request->subjectType->code,
            'subject_id' => $request->subject_id,
            'request_type' => $request->requestType->code,
            'status' => $request->status->code,
            'requested_at' => $request->requested_at->toIso8601String(),
            'resolved_at' => $request->resolved_at?->toIso8601String(),
            'days_until_deadline' => $request->daysUntilDeadline(),
            'is_overdue' => $request->isOverdue(),
        ];
    }

    /**
     * Envia notificacao de status da solicitacao.
     */
    public function notifyRequestStatus(int $requestId, string $phone): array
    {
        $request = DataRequest::with(['requestType', 'status'])->find($requestId);

        if (!$request) {
            return ['ok' => false, 'error' => 'Solicitacao nao encontrada'];
        }

        $result = $this->whatsApp->sendDataRequestNotification(
            $phone,
            $request->requestType->code,
            $request->status->code
        );

        return [
            'ok' => $result['ok'],
            'error' => $result['error'] ?? null,
        ];
    }

    /**
     * Coleta dados do titular para exportacao.
     */
    private function collectSubjectData(int $ownerTypeId, int $ownerId): array
    {
        $documents = Document::where('owner_type_id', $ownerTypeId)
            ->where('owner_id', $ownerId)
            ->with(['template.docType', 'status', 'versions', 'signatures'])
            ->get();

        $subjectTypeId = $this->mapOwnerToSubjectType($ownerTypeId);
        $consents = Consent::getHistoryForSubject($subjectTypeId, $ownerId);

        return [
            'export_info' => [
                'generated_at' => now()->toIso8601String(),
                'generated_by' => config('branding.name', 'Carinho com Voce'),
            ],
            'subject' => [
                'type' => DomainOwnerType::CODES[$ownerTypeId] ?? 'unknown',
                'id' => $ownerId,
            ],
            'documents' => $documents->map(fn ($doc) => [
                'id' => $doc->id,
                'type' => $doc->template?->docType?->label,
                'status' => $doc->status->label,
                'created_at' => $doc->created_at->toIso8601String(),
                'versions_count' => $doc->versions->count(),
                'signatures_count' => $doc->signatures->count(),
            ])->toArray(),
            'consents' => $consents->map(fn ($c) => [
                'type' => $c->consent_type,
                'granted_at' => $c->granted_at->toIso8601String(),
                'source' => $c->source,
                'revoked_at' => $c->revoked_at?->toIso8601String(),
                'is_active' => $c->isActive(),
            ])->toArray(),
        ];
    }

    /**
     * Mapeia tipo de titular para tipo de proprietario.
     */
    private function mapSubjectToOwnerType(int $subjectTypeId): int
    {
        return match ($subjectTypeId) {
            DomainConsentSubjectType::CLIENT => DomainOwnerType::CLIENT,
            DomainConsentSubjectType::CAREGIVER => DomainOwnerType::CAREGIVER,
            default => DomainOwnerType::CLIENT,
        };
    }

    /**
     * Mapeia tipo de proprietario para tipo de titular.
     */
    private function mapOwnerToSubjectType(int $ownerTypeId): int
    {
        return match ($ownerTypeId) {
            DomainOwnerType::CLIENT => DomainConsentSubjectType::CLIENT,
            DomainOwnerType::CAREGIVER => DomainConsentSubjectType::CAREGIVER,
            default => DomainConsentSubjectType::CLIENT,
        };
    }
}
