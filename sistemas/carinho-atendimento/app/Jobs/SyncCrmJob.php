<?php

namespace App\Jobs;

use App\Integrations\Crm\CrmClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCrmJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private string $action, private array $payload)
    {
    }

    public function handle(CrmClient $client): void
    {
        if ($this->action === 'lead') {
            $client->upsertLead($this->payload);
            return;
        }

        if ($this->action === 'incident') {
            $client->registerIncident($this->payload);
            return;
        }

        throw new \InvalidArgumentException('Unknown CRM action.');
    }
}
