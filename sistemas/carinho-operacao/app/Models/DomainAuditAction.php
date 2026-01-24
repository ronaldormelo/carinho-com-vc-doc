<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de ação de auditoria.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainAuditAction extends Model
{
    protected $table = 'domain_audit_action';
    
    public $timestamps = false;

    protected $fillable = [
        'id',
        'code',
        'label',
    ];

    // Constantes de ações
    const SCHEDULE_CREATED = 1;
    const SCHEDULE_UPDATED = 2;
    const SCHEDULE_CANCELED = 3;
    const CHECKIN_PERFORMED = 4;
    const CHECKOUT_PERFORMED = 5;
    const ASSIGNMENT_CREATED = 6;
    const ASSIGNMENT_CONFIRMED = 7;
    const SUBSTITUTION_PROCESSED = 8;
    const EMERGENCY_CREATED = 9;
    const EMERGENCY_RESOLVED = 10;
    const EMERGENCY_ESCALATED = 11;
    const EXCEPTION_APPROVED = 12;
    const EXCEPTION_REJECTED = 13;
    const MANUAL_OVERRIDE = 14;
}
