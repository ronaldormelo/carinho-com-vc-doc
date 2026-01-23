<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de documento legal.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainLegalDocType extends Model
{
    protected $table = 'domain_legal_doc_type';
    public $timestamps = false;

    public const PRIVACY = 1;
    public const TERMS = 2;
    public const CANCELLATION = 3;
    public const EMERGENCY = 4;
    public const PAYMENT = 5;
    public const CAREGIVER_TERMS = 6;
}
