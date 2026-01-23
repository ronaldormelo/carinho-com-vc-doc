<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Documento.
 *
 * @property int $id
 * @property int $owner_type_id
 * @property int $owner_id
 * @property int $template_id
 * @property int $status_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Document extends Model
{
    protected $table = 'documents';

    protected $fillable = [
        'owner_type_id',
        'owner_id',
        'template_id',
        'status_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ownerType(): BelongsTo
    {
        return $this->belongsTo(DomainOwnerType::class, 'owner_type_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainDocumentStatus::class, 'status_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'document_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class, 'document_id');
    }

    /**
     * Obtem a versao mais recente.
     */
    public function latestVersion(): ?DocumentVersion
    {
        return $this->versions()->latest('created_at')->first();
    }

    /**
     * Verifica se o documento esta assinado.
     */
    public function isSigned(): bool
    {
        return $this->status_id === DomainDocumentStatus::SIGNED;
    }

    /**
     * Verifica se o documento esta arquivado.
     */
    public function isArchived(): bool
    {
        return $this->status_id === DomainDocumentStatus::ARCHIVED;
    }

    /**
     * Verifica se o documento eh rascunho.
     */
    public function isDraft(): bool
    {
        return $this->status_id === DomainDocumentStatus::DRAFT;
    }

    /**
     * Marca como assinado.
     */
    public function markAsSigned(): bool
    {
        $this->status_id = DomainDocumentStatus::SIGNED;

        return $this->save();
    }

    /**
     * Marca como arquivado.
     */
    public function markAsArchived(): bool
    {
        $this->status_id = DomainDocumentStatus::ARCHIVED;

        return $this->save();
    }

    /**
     * Busca documentos por proprietario.
     */
    public static function findByOwner(int $ownerTypeId, int $ownerId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('owner_type_id', $ownerTypeId)
            ->where('owner_id', $ownerId)
            ->with(['status', 'template.docType'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Scope para documentos de cliente.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('owner_type_id', DomainOwnerType::CLIENT)
            ->where('owner_id', $clientId);
    }

    /**
     * Scope para documentos de cuidador.
     */
    public function scopeForCaregiver($query, int $caregiverId)
    {
        return $query->where('owner_type_id', DomainOwnerType::CAREGIVER)
            ->where('owner_id', $caregiverId);
    }
}
