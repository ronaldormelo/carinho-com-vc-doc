<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Domain\DomainApiKeyStatus;

/**
 * Chave de API para autenticacao de sistemas externos.
 *
 * @property int $id
 * @property string $name
 * @property string $key_hash
 * @property array $permissions_json
 * @property int $status_id
 * @property \Carbon\Carbon|null $last_used_at
 */
class ApiKey extends Model
{
    public $timestamps = false;

    protected $table = 'api_keys';

    protected $fillable = [
        'name',
        'key_hash',
        'permissions_json',
        'status_id',
        'last_used_at',
    ];

    protected $casts = [
        'permissions_json' => 'array',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'key_hash',
    ];

    /**
     * Relacionamento com status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DomainApiKeyStatus::class, 'status_id');
    }

    /**
     * Verifica se a chave esta ativa.
     */
    public function isActive(): bool
    {
        return $this->status_id === DomainApiKeyStatus::ACTIVE;
    }

    /**
     * Verifica se possui permissao.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions_json ?? [];

        return in_array('*', $permissions) || in_array($permission, $permissions);
    }

    /**
     * Valida a chave fornecida.
     */
    public function validateKey(string $key): bool
    {
        return Hash::check($key, $this->key_hash);
    }

    /**
     * Atualiza timestamp de ultimo uso.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Gera uma nova API key.
     */
    public static function generate(string $name, array $permissions = ['*']): array
    {
        $plainKey = 'carinho_' . Str::random(32);

        $apiKey = self::create([
            'name' => $name,
            'key_hash' => Hash::make($plainKey),
            'permissions_json' => $permissions,
            'status_id' => DomainApiKeyStatus::ACTIVE,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_key' => $plainKey,
        ];
    }

    /**
     * Revoga a chave.
     */
    public function revoke(): void
    {
        $this->update(['status_id' => DomainApiKeyStatus::REVOKED]);
    }

    /**
     * Escopo para chaves ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', DomainApiKeyStatus::ACTIVE);
    }

    /**
     * Busca por nome do sistema.
     */
    public static function findByName(string $name): ?self
    {
        return self::active()->where('name', $name)->first();
    }
}
