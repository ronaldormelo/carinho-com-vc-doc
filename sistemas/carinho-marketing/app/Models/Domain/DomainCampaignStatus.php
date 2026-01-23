<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * Status de campanhas de marketing.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
class DomainCampaignStatus extends Model
{
    public $timestamps = false;

    protected $table = 'domain_campaign_status';

    protected $fillable = ['code', 'label'];

    public const PLANNED = 1;
    public const ACTIVE = 2;
    public const PAUSED = 3;
    public const FINISHED = 4;

    /**
     * Retorna status planejado.
     */
    public static function planned(): int
    {
        return self::PLANNED;
    }

    /**
     * Retorna status ativo.
     */
    public static function active(): int
    {
        return self::ACTIVE;
    }

    /**
     * Retorna status pausado.
     */
    public static function paused(): int
    {
        return self::PAUSED;
    }

    /**
     * Retorna status finalizado.
     */
    public static function finished(): int
    {
        return self::FINISHED;
    }

    /**
     * Verifica se a campanha esta ativa.
     */
    public function isActive(): bool
    {
        return $this->id === self::ACTIVE;
    }

    /**
     * Verifica se a campanha pode ser iniciada.
     */
    public function canBeActivated(): bool
    {
        return in_array($this->id, [self::PLANNED, self::PAUSED]);
    }

    /**
     * Verifica se a campanha esta em execucao ou pode ser retomada.
     */
    public function isRunning(): bool
    {
        return in_array($this->id, [self::ACTIVE, self::PAUSED]);
    }
}
