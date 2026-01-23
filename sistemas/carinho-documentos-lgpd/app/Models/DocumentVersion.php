<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Versao de documento.
 *
 * @property int $id
 * @property int $document_id
 * @property string $version
 * @property string $file_url
 * @property string $checksum
 * @property Carbon $created_at
 */
class DocumentVersion extends Model
{
    protected $table = 'document_versions';

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'version',
        'file_url',
        'checksum',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Cria checksum do arquivo.
     */
    public static function createChecksum(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Verifica integridade do arquivo.
     */
    public function verifyIntegrity(string $content): bool
    {
        return hash_equals($this->checksum, self::createChecksum($content));
    }

    /**
     * Gera numero de versao automatico.
     */
    public static function generateVersionNumber(int $documentId): string
    {
        $lastVersion = static::where('document_id', $documentId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastVersion) {
            return '1.0';
        }

        $parts = explode('.', $lastVersion->version);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        return $major . '.' . ($minor + 1);
    }

    /**
     * Extrai o caminho S3 da URL.
     */
    public function getS3Path(): string
    {
        $url = $this->file_url;

        // Remove prefixo do bucket se existir
        if (str_contains($url, '.s3.')) {
            $parsed = parse_url($url);

            return ltrim($parsed['path'] ?? '', '/');
        }

        return $url;
    }
}
