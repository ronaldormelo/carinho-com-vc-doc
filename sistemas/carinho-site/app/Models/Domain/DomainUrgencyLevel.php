<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Nivel de urgencia da demanda.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 * @property int $priority
 */
class DomainUrgencyLevel extends Model
{
    protected $table = 'domain_urgency_level';
    public $timestamps = false;

    public const HOJE = 1;
    public const SEMANA = 2;
    public const SEM_DATA = 3;
}
