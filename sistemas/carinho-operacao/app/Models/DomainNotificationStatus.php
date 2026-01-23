<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de notificacao.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainNotificationStatus extends Model
{
    protected $table = 'domain_notification_status';
    public $timestamps = false;

    protected $fillable = ['code', 'label'];

    public const QUEUED = 1;
    public const SENT = 2;
    public const FAILED = 3;
}
