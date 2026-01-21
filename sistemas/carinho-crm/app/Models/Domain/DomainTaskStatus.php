<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

class DomainTaskStatus extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_task_status';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para códigos
    public const OPEN = 1;
    public const DONE = 2;
    public const CANCELED = 3;

    public function tasks()
    {
        return $this->hasMany(\App\Models\Task::class, 'status_id');
    }
}
