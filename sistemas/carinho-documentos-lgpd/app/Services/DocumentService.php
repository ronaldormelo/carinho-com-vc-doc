<?php

namespace App\Services;

use App\Integrations\Storage\S3StorageClient;
use App\Models\AccessLog;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentVersion;
use App\Models\DomainDocumentStatus;
use App\Models\DomainOwnerType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de documentos.
 */
class DocumentService
{
    public function __construct(
        private S3StorageClient $storage
    ) {}

    /**
     * Cria novo documento a partir de template.
     */
    public function createFromTemplate(
        int $ownerTypeId,
        int $ownerId,
        int $templateId,
        array $variables = []
    ): ?Document {
        try {
            return DB::transaction(function () use ($ownerTypeId, $ownerId, $templateId, $variables) {
                $template = DocumentTemplate::findOrFail($templateId);

                // Cria documento
                $document = Document::create([
                    'owner_type_id' => $ownerTypeId,
                    'owner_id' => $ownerId,
                    'template_id' => $templateId,
                    'status_id' => DomainDocumentStatus::DRAFT,
                ]);

                // Renderiza conteudo do template
                $content = $template->render($variables);

                // Gera PDF e faz upload
                $pdfContent = $this->generatePdf($content);
                $path = $this->buildDocumentPath($ownerTypeId, $ownerId, $document->id);

                $uploadResult = $this->storage->upload($pdfContent, $path, [
                    'document_id' => (string) $document->id,
                    'template_id' => (string) $templateId,
                    'mime_type' => 'application/pdf',
                ]);

                if (!$uploadResult['ok']) {
                    throw new \Exception('Falha no upload do documento: ' . ($uploadResult['error'] ?? 'erro desconhecido'));
                }

                // Cria versao
                DocumentVersion::create([
                    'document_id' => $document->id,
                    'version' => '1.0',
                    'file_url' => $path,
                    'checksum' => $uploadResult['checksum'],
                    'created_at' => now(),
                ]);

                Log::info('Document created', [
                    'document_id' => $document->id,
                    'template_id' => $templateId,
                ]);

                return $document;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create document', [
                'error' => $e->getMessage(),
                'owner_type_id' => $ownerTypeId,
                'owner_id' => $ownerId,
            ]);

            return null;
        }
    }

    /**
     * Faz upload de documento.
     */
    public function upload(
        UploadedFile $file,
        int $ownerTypeId,
        int $ownerId,
        int $templateId,
        array $metadata = []
    ): ?Document {
        try {
            // Valida arquivo
            if (!$this->validateFile($file)) {
                throw new \Exception('Tipo de arquivo nao permitido');
            }

            return DB::transaction(function () use ($file, $ownerTypeId, $ownerId, $templateId, $metadata) {
                // Cria documento
                $document = Document::create([
                    'owner_type_id' => $ownerTypeId,
                    'owner_id' => $ownerId,
                    'template_id' => $templateId,
                    'status_id' => DomainDocumentStatus::DRAFT,
                ]);

                // Faz upload
                $path = $this->buildDocumentPath($ownerTypeId, $ownerId, $document->id);
                $path .= '.' . $file->getClientOriginalExtension();

                $uploadResult = $this->storage->upload($file, $path, array_merge($metadata, [
                    'document_id' => (string) $document->id,
                    'original_name' => $file->getClientOriginalName(),
                ]));

                if (!$uploadResult['ok']) {
                    throw new \Exception('Falha no upload: ' . ($uploadResult['error'] ?? 'erro desconhecido'));
                }

                // Cria versao
                DocumentVersion::create([
                    'document_id' => $document->id,
                    'version' => '1.0',
                    'file_url' => $path,
                    'checksum' => $uploadResult['checksum'],
                    'created_at' => now(),
                ]);

                return $document;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to upload document', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtem URL assinada para download.
     */
    public function getSignedUrl(int $documentId, int $actorId, string $ipAddress): ?array
    {
        $document = Document::with('versions')->find($documentId);

        if (!$document) {
            return null;
        }

        $latestVersion = $document->latestVersion();
        if (!$latestVersion) {
            return null;
        }

        // Registra log de acesso
        AccessLog::logView($documentId, $actorId, $ipAddress);

        // Gera URL assinada
        $result = $this->storage->getSignedUrl(
            $latestVersion->file_url,
            config('documentos.signed_urls.default_expiration', 60)
        );

        if (!$result['ok']) {
            return null;
        }

        return [
            'url' => $result['url'],
            'expires_at' => $result['expires_at'],
            'document_id' => $documentId,
            'version' => $latestVersion->version,
        ];
    }

    /**
     * Faz download do documento.
     */
    public function download(int $documentId, int $actorId, string $ipAddress): ?array
    {
        $document = Document::with('versions')->find($documentId);

        if (!$document) {
            return null;
        }

        $latestVersion = $document->latestVersion();
        if (!$latestVersion) {
            return null;
        }

        // Registra log de acesso
        AccessLog::logDownload($documentId, $actorId, $ipAddress);

        // Faz download
        $result = $this->storage->download($latestVersion->file_url);

        if (!$result['ok']) {
            return null;
        }

        return [
            'content' => $result['content'],
            'content_type' => $result['content_type'],
            'size' => $result['size'],
            'filename' => $this->generateDownloadFilename($document),
        ];
    }

    /**
     * Cria nova versao do documento.
     */
    public function createVersion(int $documentId, UploadedFile $file): ?DocumentVersion
    {
        try {
            $document = Document::findOrFail($documentId);

            // Gera nova versao
            $versionNumber = DocumentVersion::generateVersionNumber($documentId);

            // Faz upload
            $path = $this->buildDocumentPath(
                $document->owner_type_id,
                $document->owner_id,
                $document->id
            ) . "_v{$versionNumber}." . $file->getClientOriginalExtension();

            $content = file_get_contents($file->getRealPath());
            $uploadResult = $this->storage->upload($file, $path);

            if (!$uploadResult['ok']) {
                throw new \Exception('Falha no upload da versao');
            }

            // Cria versao
            $version = DocumentVersion::create([
                'document_id' => $documentId,
                'version' => $versionNumber,
                'file_url' => $path,
                'checksum' => $uploadResult['checksum'],
                'created_at' => now(),
            ]);

            // Atualiza data de modificacao do documento
            $document->touch();

            return $version;
        } catch (\Throwable $e) {
            Log::error('Failed to create version', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Lista documentos por proprietario.
     */
    public function listByOwner(int $ownerTypeId, int $ownerId): array
    {
        $cacheKey = "documents:owner:{$ownerTypeId}:{$ownerId}";

        return Cache::remember($cacheKey, config('documentos.cache.metadata_ttl', 30) * 60, function () use ($ownerTypeId, $ownerId) {
            return Document::findByOwner($ownerTypeId, $ownerId)->toArray();
        });
    }

    /**
     * Lista documentos de cliente.
     */
    public function listByClient(int $clientId): array
    {
        return $this->listByOwner(DomainOwnerType::CLIENT, $clientId);
    }

    /**
     * Lista documentos de cuidador.
     */
    public function listByCaregiver(int $caregiverId): array
    {
        return $this->listByOwner(DomainOwnerType::CAREGIVER, $caregiverId);
    }

    /**
     * Exclui documento.
     */
    public function delete(int $documentId, int $actorId, string $ipAddress): bool
    {
        try {
            $document = Document::with('versions')->findOrFail($documentId);

            return DB::transaction(function () use ($document, $actorId, $ipAddress) {
                // Registra log de acesso
                AccessLog::logDelete($document->id, $actorId, $ipAddress);

                // Exclui arquivos do S3
                foreach ($document->versions as $version) {
                    $this->storage->delete($version->file_url);
                }

                // Exclui documento (cascade deleta versoes e assinaturas)
                $document->delete();

                // Limpa cache
                $this->clearOwnerCache($document->owner_type_id, $document->owner_id);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to delete document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Valida arquivo.
     */
    private function validateFile(UploadedFile $file): bool
    {
        $allowedMimes = config('documentos.upload.allowed_mimes', [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ]);

        $maxSizeMb = config('documentos.upload.max_size_mb', 25);

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        if ($file->getSize() > $maxSizeMb * 1024 * 1024) {
            return false;
        }

        return true;
    }

    /**
     * Gera PDF a partir de HTML.
     */
    private function generatePdf(string $html): string
    {
        // Implementacao simplificada - em producao usar biblioteca como DomPDF ou wkhtmltopdf
        // Por enquanto retorna o HTML como placeholder
        return $html;
    }

    /**
     * Constroi caminho do documento no S3.
     */
    private function buildDocumentPath(int $ownerTypeId, int $ownerId, int $documentId): string
    {
        $prefix = match ($ownerTypeId) {
            DomainOwnerType::CLIENT => 'clients',
            DomainOwnerType::CAREGIVER => 'caregivers',
            DomainOwnerType::COMPANY => 'company',
            default => 'other',
        };

        $date = now();

        return "{$prefix}/{$ownerId}/{$date->format('Y')}/{$date->format('m')}/doc_{$documentId}";
    }

    /**
     * Gera nome de arquivo para download.
     */
    private function generateDownloadFilename(Document $document): string
    {
        $docType = $document->template?->docType?->code ?? 'documento';
        $date = $document->created_at->format('Ymd');

        return "{$docType}_{$date}_{$document->id}.pdf";
    }

    /**
     * Limpa cache de proprietario.
     */
    private function clearOwnerCache(int $ownerTypeId, int $ownerId): void
    {
        Cache::forget("documents:owner:{$ownerTypeId}:{$ownerId}");
    }
}
