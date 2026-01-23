<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingHistory extends Model
{
    public $timestamps = false;

    protected $table = 'setting_history';

    protected $fillable = [
        'setting_id',
        'old_value',
        'new_value',
        'changed_by',
        'change_reason',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Relacionamento com configuração.
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'setting_id');
    }
}
