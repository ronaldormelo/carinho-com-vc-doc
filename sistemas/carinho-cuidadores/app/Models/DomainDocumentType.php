<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainDocumentType extends Model
{
    protected $table = 'domain_document_type';

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'label'];

    public const ID = 1;
    public const CPF = 2;
    public const ADDRESS = 3;
    public const CERTIFICATE = 4;
    public const OTHER = 5;

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public static function required(): array
    {
        return config('cuidadores.triagem.documentos_obrigatorios', ['id', 'cpf', 'address']);
    }

    public static function optional(): array
    {
        return config('cuidadores.triagem.documentos_opcionais', ['certificate', 'other']);
    }
}
