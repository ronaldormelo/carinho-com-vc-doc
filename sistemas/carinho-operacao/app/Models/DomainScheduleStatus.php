<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Status do agendamento.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainScheduleStatus extends Model
{
    protected $table = 'domain_schedule_status';
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    public const PLANNED = 1;
    public const IN_PROGRESS = 2;
    public const DONE = 3;
    public const MISSED = 4;
}
