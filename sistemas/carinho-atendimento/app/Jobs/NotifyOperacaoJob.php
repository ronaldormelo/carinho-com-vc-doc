<?php

namespace App\Jobs;

use App\Integrations\Operacao\OperacaoClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyOperacaoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private array $payload)
    {
    }

    public function handle(OperacaoClient $client): void
    {
        $client->notifyEmergency($this->payload);
    }
}
