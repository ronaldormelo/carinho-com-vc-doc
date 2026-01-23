<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Trait para criptografia de campos sensíveis (LGPD compliance)
 */
trait HasEncryptedFields
{
    /**
     * Campos que devem ser criptografados (definir no Model)
     */
    protected function getEncryptedFields(): array
    {
        return $this->encrypted ?? [];
    }

    /**
     * Boot do trait
     */
    public static function bootHasEncryptedFields(): void
    {
        static::saving(function ($model) {
            foreach ($model->getEncryptedFields() as $field) {
                if (isset($model->attributes[$field]) && $model->attributes[$field] !== null) {
                    // Evitar dupla criptografia
                    if (!$model->isEncrypted($model->attributes[$field])) {
                        $model->attributes[$field] = Crypt::encryptString($model->attributes[$field]);
                    }
                }
            }
        });
    }

    /**
     * Obter atributo descriptografado
     */
    protected function getDecryptedAttribute(string $field): ?string
    {
        $value = $this->attributes[$field] ?? null;

        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Se não conseguir descriptografar, retorna valor original
            // (pode ser dado legado não criptografado)
            return $value;
        }
    }

    /**
     * Verifica se um valor já está criptografado
     */
    protected function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }

    /**
     * Buscar por campo criptografado (necessário descriptografar todos os registros)
     * ATENÇÃO: Use com cautela em grandes volumes de dados
     */
    public function scopeWhereEncrypted($query, string $field, string $value)
    {
        return $query->whereRaw("1=1")->get()->filter(function ($model) use ($field, $value) {
            return $model->{$field} === $value;
        });
    }
}
