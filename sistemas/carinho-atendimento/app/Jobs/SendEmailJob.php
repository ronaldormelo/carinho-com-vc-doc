<?php

namespace App\Jobs;

use App\Integrations\Email\EmailClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private string $type, private string $to, private array $payload)
    {
    }

    public function handle(EmailClient $client): void
    {
        if ($this->type === 'proposal') {
            $client->sendProposal($this->to, $this->payload);
            return;
        }

        if ($this->type === 'contract') {
            $client->sendContract($this->to, $this->payload);
            return;
        }

        throw new \InvalidArgumentException('Unknown email type.');
    }
}
