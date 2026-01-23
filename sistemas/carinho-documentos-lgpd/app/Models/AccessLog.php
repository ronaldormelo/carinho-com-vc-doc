<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Log de acesso a documento.
 *
 * @property int $id
 * @property int $document_id
 * @property int $actor_id
 * @property int $action_id
 * @property string $ip_address
 * @property Carbon $created_at
 */
class AccessLog extends Model
{
    protected $table = 'access_logs';

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'actor_id',
        'action_id',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(DomainAccessAction::class, 'action_id');
    }

    /**
     * Registra log de acesso.
     */
    public static function log(int $documentId, int $actorId, int $actionId, string $ipAddress): self
    {
        return static::create([
            'document_id' => $documentId,
            'actor_id' => $actorId,
            'action_id' => $actionId,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    /**
     * Registra visualizacao.
     */
    public static function logView(int $documentId, int $actorId, string $ipAddress): self
    {
        return static::log($documentId, $actorId, DomainAccessAction::VIEW, $ipAddress);
    }

    /**
     * Registra download.
     */
    public static function logDownload(int $documentId, int $actorId, string $ipAddress): self
    {
        return static::log($documentId, $actorId, DomainAccessAction::DOWNLOAD, $ipAddress);
    }

    /**
     * Registra assinatura.
     */
    public static function logSign(int $documentId, int $actorId, string $ipAddress): self
    {
        return static::log($documentId, $actorId, DomainAccessAction::SIGN, $ipAddress);
    }

    /**
     * Registra exclusao.
     */
    public static function logDelete(int $documentId, int $actorId, string $ipAddress): self
    {
        return static::log($documentId, $actorId, DomainAccessAction::DELETE, $ipAddress);
    }

    /**
     * Busca logs por documento.
     */
    public static function findByDocument(int $documentId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('document_id', $documentId)
            ->with('action')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca logs por ator.
     */
    public static function findByActor(int $actorId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('actor_id', $actorId)
            ->with(['document', 'action'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
