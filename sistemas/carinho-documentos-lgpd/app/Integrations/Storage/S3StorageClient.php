<?php

namespace App\Integrations\Storage;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Cliente para integracao com AWS S3.
 *
 * Documentacao: https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-examples.html
 *
 * Funcionalidades:
 * - Upload de arquivos com criptografia server-side (AES-256)
 * - Download via URLs pre-assinadas com expiracao
 * - Versionamento de objetos
 * - Gerenciamento de ciclo de vida
 */
class S3StorageClient
{
    private ?S3Client $client = null;

    private string $bucket;

    private string $region;

    public function __construct()
    {
        $this->bucket = config('integrations.aws.bucket', 'carinho-documentos');
        $this->region = config('integrations.aws.region', 'sa-east-1');
    }

    /**
     * Obtem cliente S3.
     */
    private function getClient(): S3Client
    {
        if ($this->client === null) {
            $this->client = new S3Client([
                'version' => 'latest',
                'region' => $this->region,
                'credentials' => [
                    'key' => config('integrations.aws.key'),
                    'secret' => config('integrations.aws.secret'),
                ],
            ]);
        }

        return $this->client;
    }

    /**
     * Faz upload de arquivo para o S3.
     *
     * @param  UploadedFile|string  $file  Arquivo ou conteudo
     * @param  string  $path  Caminho no bucket
     * @param  array  $metadata  Metadados adicionais
     */
    public function upload($file, string $path, array $metadata = []): array
    {
        try {
            $content = $file instanceof UploadedFile
                ? file_get_contents($file->getRealPath())
                : $file;

            $mimeType = $file instanceof UploadedFile
                ? $file->getMimeType()
                : ($metadata['mime_type'] ?? 'application/octet-stream');

            // Calcula checksum SHA-256
            $checksum = hash('sha256', $content);

            $result = $this->getClient()->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $content,
                'ContentType' => $mimeType,
                'ServerSideEncryption' => config('integrations.aws.encryption', 'AES256'),
                'CacheControl' => 'max-age=0, no-cache, no-store, must-revalidate',
                'Metadata' => array_merge($metadata, [
                    'checksum' => $checksum,
                    'uploaded_at' => now()->toIso8601String(),
                    'source' => 'carinho-documentos-lgpd',
                ]),
            ]);

            Log::info('S3 upload successful', [
                'path' => $path,
                'size' => strlen($content),
            ]);

            return [
                'ok' => true,
                'path' => $path,
                'url' => $result['ObjectURL'] ?? $this->buildUrl($path),
                'checksum' => $checksum,
                'size' => strlen($content),
                'version_id' => $result['VersionId'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('S3 upload failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Faz upload de arquivo de cliente.
     */
    public function uploadClientDocument(int $clientId, UploadedFile $file, string $docType): array
    {
        $extension = $file->getClientOriginalExtension();
        $filename = $this->generateFilename($docType, $extension);
        $path = $this->getClientPath($clientId) . '/' . $filename;

        return $this->upload($file, $path, [
            'client_id' => (string) $clientId,
            'doc_type' => $docType,
        ]);
    }

    /**
     * Faz upload de arquivo de cuidador.
     */
    public function uploadCaregiverDocument(int $caregiverId, UploadedFile $file, string $docType): array
    {
        $extension = $file->getClientOriginalExtension();
        $filename = $this->generateFilename($docType, $extension);
        $path = $this->getCaregiverPath($caregiverId) . '/' . $filename;

        return $this->upload($file, $path, [
            'caregiver_id' => (string) $caregiverId,
            'doc_type' => $docType,
        ]);
    }

    /**
     * Faz upload de contrato.
     */
    public function uploadContract(string $content, string $contractType, int $ownerId): array
    {
        $date = now();
        $filename = $this->generateFilename($contractType, 'pdf');
        $path = sprintf(
            '%s/%s/%s/%s',
            config('integrations.aws.prefixes.contracts', 'contracts'),
            $date->format('Y'),
            $date->format('m'),
            $filename
        );

        return $this->upload($content, $path, [
            'contract_type' => $contractType,
            'owner_id' => (string) $ownerId,
        ]);
    }

    /**
     * Gera URL pre-assinada para download.
     */
    public function getSignedUrl(string $path, int $expirationMinutes = null): array
    {
        try {
            $expiration = $expirationMinutes
                ?? config('integrations.aws.signed_url_expiration', 60);

            $cmd = $this->getClient()->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            $request = $this->getClient()->createPresignedRequest(
                $cmd,
                "+{$expiration} minutes"
            );

            $signedUrl = (string) $request->getUri();

            return [
                'ok' => true,
                'url' => $signedUrl,
                'expires_at' => now()->addMinutes($expiration)->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::error('S3 signed URL generation failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Gera URL pre-assinada para upload direto.
     */
    public function getUploadUrl(string $path, string $mimeType, int $expirationMinutes = 15): array
    {
        try {
            $cmd = $this->getClient()->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'ContentType' => $mimeType,
                'ServerSideEncryption' => config('integrations.aws.encryption', 'AES256'),
            ]);

            $request = $this->getClient()->createPresignedRequest(
                $cmd,
                "+{$expirationMinutes} minutes"
            );

            return [
                'ok' => true,
                'url' => (string) $request->getUri(),
                'path' => $path,
                'expires_at' => now()->addMinutes($expirationMinutes)->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::error('S3 upload URL generation failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Faz download do arquivo.
     */
    public function download(string $path): array
    {
        try {
            $result = $this->getClient()->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            $content = (string) $result['Body'];

            return [
                'ok' => true,
                'content' => $content,
                'content_type' => $result['ContentType'] ?? 'application/octet-stream',
                'size' => $result['ContentLength'] ?? strlen($content),
                'metadata' => $result['Metadata'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('S3 download failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica se arquivo existe.
     */
    public function exists(string $path): bool
    {
        try {
            return $this->getClient()->doesObjectExist($this->bucket, $path);
        } catch (\Throwable $e) {
            Log::warning('S3 existence check failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Exclui arquivo.
     */
    public function delete(string $path): array
    {
        try {
            $this->getClient()->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            Log::info('S3 delete successful', ['path' => $path]);

            return [
                'ok' => true,
                'path' => $path,
            ];
        } catch (\Throwable $e) {
            Log::error('S3 delete failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Copia arquivo.
     */
    public function copy(string $sourcePath, string $destinationPath): array
    {
        try {
            $this->getClient()->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $destinationPath,
                'CopySource' => "{$this->bucket}/{$sourcePath}",
                'ServerSideEncryption' => config('integrations.aws.encryption', 'AES256'),
            ]);

            return [
                'ok' => true,
                'source' => $sourcePath,
                'destination' => $destinationPath,
            ];
        } catch (\Throwable $e) {
            Log::error('S3 copy failed', [
                'source' => $sourcePath,
                'destination' => $destinationPath,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Lista objetos em um prefixo.
     */
    public function listObjects(string $prefix, int $maxKeys = 1000): array
    {
        try {
            $result = $this->getClient()->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
                'MaxKeys' => $maxKeys,
            ]);

            $objects = [];
            foreach ($result['Contents'] ?? [] as $object) {
                $objects[] = [
                    'key' => $object['Key'],
                    'size' => $object['Size'],
                    'last_modified' => $object['LastModified']->format('c'),
                ];
            }

            return [
                'ok' => true,
                'objects' => $objects,
                'count' => count($objects),
            ];
        } catch (\Throwable $e) {
            Log::error('S3 list objects failed', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtem metadados do objeto.
     */
    public function getMetadata(string $path): array
    {
        try {
            $result = $this->getClient()->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return [
                'ok' => true,
                'content_type' => $result['ContentType'] ?? null,
                'size' => $result['ContentLength'] ?? null,
                'last_modified' => $result['LastModified'] ?? null,
                'metadata' => $result['Metadata'] ?? [],
                'version_id' => $result['VersionId'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Gera nome de arquivo unico.
     */
    private function generateFilename(string $docType, string $extension): string
    {
        $timestamp = now()->format('Ymd_His');
        $uuid = Str::uuid()->toString();

        return "{$docType}_{$timestamp}_{$uuid}.{$extension}";
    }

    /**
     * Obtem caminho para documentos de cliente.
     */
    private function getClientPath(int $clientId): string
    {
        $prefix = config('integrations.aws.prefixes.clients', 'clients');

        return "{$prefix}/{$clientId}";
    }

    /**
     * Obtem caminho para documentos de cuidador.
     */
    private function getCaregiverPath(int $caregiverId): string
    {
        $prefix = config('integrations.aws.prefixes.caregivers', 'caregivers');

        return "{$prefix}/{$caregiverId}";
    }

    /**
     * Constroi URL do objeto.
     */
    private function buildUrl(string $path): string
    {
        return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$path}";
    }
}
