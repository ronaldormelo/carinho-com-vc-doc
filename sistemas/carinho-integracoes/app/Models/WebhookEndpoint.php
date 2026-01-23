<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Domain\DomainEndpointStatus;

/**
 * Endpoint de webhook para notificacao de eventos.
 *
 * @property int $id
 * @property string $system_name
 * @property string $url
 * @property string $secret
 * @property int $status_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class WebhookEndpoint extends Model
{
    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'system_name',
        'url',
        'secret',
        'status_id',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainEndpointStatus::class, 'status_id');
    }

    /**
     * Relacionamento com entregas.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'endpoint_id');
    }

    /**
     * Verifica se o endpoint esta ativo.
     */
    public function isActive(): bool
    {
        return $this->status_id === DomainEndpointStatus::ACTIVE;
    }

    /**
     * Gera assinatura para payload.
     */
    public function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }

    /**
     * Valida assinatura recebida.
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        $expected = $this->generateSignature($payload);

        return hash_equals($expected, $signature);
    }

    /**
     * Cria novo endpoint com secret aleatorio.
     */
    public static function createWithSecret(string $systemName, string $url): self
    {
        return self::create([
            'system_name' => $systemName,
            'url' => $url,
            'secret' => Str::random(64),
            'status_id' => DomainEndpointStatus::ACTIVE,
        ]);
    }

    /**
     * Desativa o endpoint.
     */
    public function deactivate(): void
    {
        $this->update(['status_id' => DomainEndpointStatus::INACTIVE]);
    }

    /**
     * Ativa o endpoint.
     */
    public function activate(): void
    {
        $this->update(['status_id' => DomainEndpointStatus::ACTIVE]);
    }

    /**
     * Escopo para endpoints ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', DomainEndpointStatus::ACTIVE);
    }

    /**
     * Busca endpoints por sistema.
     */
    public static function forSystem(string $systemName)
    {
        return self::active()->where('system_name', $systemName)->get();
    }
}
