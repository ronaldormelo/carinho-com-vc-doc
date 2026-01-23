<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Template de documento.
 *
 * @property int $id
 * @property int $doc_type_id
 * @property string $version
 * @property string $content
 * @property bool $active
 */
class DocumentTemplate extends Model
{
    protected $table = 'document_templates';

    public $timestamps = false;

    protected $fillable = [
        'doc_type_id',
        'version',
        'content',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function docType(): BelongsTo
    {
        return $this->belongsTo(DomainDocType::class, 'doc_type_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'template_id');
    }

    /**
     * Obtem template ativo por tipo.
     */
    public static function getActiveByType(int $docTypeId): ?self
    {
        return static::where('doc_type_id', $docTypeId)
            ->where('active', true)
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Renderiza o template com variaveis.
     */
    public function render(array $variables = []): string
    {
        $content = $this->content;

        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", (string) $value, $content);
        }

        return $content;
    }

    /**
     * Desativa template.
     */
    public function deactivate(): bool
    {
        $this->active = false;

        return $this->save();
    }

    /**
     * Ativa template e desativa outros do mesmo tipo.
     */
    public function activate(): bool
    {
        // Desativa outros templates do mesmo tipo
        static::where('doc_type_id', $this->doc_type_id)
            ->where('id', '!=', $this->id)
            ->update(['active' => false]);

        $this->active = true;

        return $this->save();
    }
}
