<?php

namespace App\Integrations\Email;

use Illuminate\Support\Facades\Mail;

class EmailClient
{
    public function sendProposal(string $to, array $data): void
    {
        $this->send('emails.proposta', $to, 'Proposta de cuidado domiciliar', $data);
    }

    public function sendContract(string $to, array $data): void
    {
        $this->send('emails.contrato', $to, 'Contrato de prestacao de servico', $data);
    }

    private function send(string $view, string $to, string $subject, array $data): void
    {
        $from = config('integrations.email.from');
        $replyTo = config('integrations.email.reply_to') ?? config('branding.email.reply_to');
        $brandName = config('branding.name', 'Carinho');

        Mail::send($view, $data, function ($message) use ($to, $subject, $from, $replyTo, $brandName) {
            $message->to($to)->subject($subject);

            if ($from) {
                $message->from($from, $brandName);
            }

            if ($replyTo) {
                $message->replyTo($replyTo);
            }
        });
    }
}
