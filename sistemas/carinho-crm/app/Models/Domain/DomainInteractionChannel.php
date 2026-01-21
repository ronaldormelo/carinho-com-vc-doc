<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCacheableQueries;

class DomainInteractionChannel extends Model
{
    use HasCacheableQueries;

    protected $table = 'domain_interaction_channel';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'label'];

    // Constantes para códigos
    public const WHATSAPP = 1;
    public const EMAIL = 2;
    public const PHONE = 3;

    public function interactions()
    {
        return $this->hasMany(\App\Models\Interaction::class, 'channel_id');
    }
}
