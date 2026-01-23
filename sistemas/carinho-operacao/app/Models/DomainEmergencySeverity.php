<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Severidade de emergencia.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainEmergencySeverity extends Model
{
    protected $table = 'domain_emergency_severity';
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    public const LOW = 1;
    public const MEDIUM = 2;
    public const HIGH = 3;
    public const CRITICAL = 4;
}
