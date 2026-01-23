<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Servico de envio de e-mails.
 *
 * Centraliza envio de emails transacionais e notificacoes.
 */
class EmailService
{
    /**
     * Envia e-mail de boas-vindas para novo cliente.
     */
    public function sendWelcomeEmail(array $client): bool
    {
        try {
            Mail::send('emails.boas-vindas', [
                'nome' => $client['name'],
                'email' => $client['email'],
            ], function ($message) use ($client) {
                $message->to($client['email'], $client['name'])
                    ->subject('Bem-vindo(a) ao Carinho com Você!')
                    ->from(
                        config('branding.email.from_address'),
                        config('branding.email.from_name')
                    );
            });

            Log::info('Welcome email sent', ['email' => $client['email']]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome email', [
                'email' => $client['email'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia e-mail de confirmacao de cadastro.
     */
    public function sendSignupConfirmation(array $data): bool
    {
        try {
            Mail::send('emails.confirmacao-cadastro', [
                'nome' => $data['name'],
                'tipo' => $data['type'], // client ou caregiver
            ], function ($message) use ($data) {
                $message->to($data['email'], $data['name'])
                    ->subject('Cadastro realizado com sucesso!')
                    ->from(
                        config('branding.email.from_address'),
                        config('branding.email.from_name')
                    );
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send signup confirmation', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia e-mail com contrato para assinatura.
     */
    public function sendContractEmail(array $data): bool
    {
        try {
            Mail::send('emails.contrato', [
                'nome' => $data['name'],
                'link_assinatura' => $data['signature_link'],
                'validade' => $data['expires_at'],
            ], function ($message) use ($data) {
                $message->to($data['email'], $data['name'])
                    ->subject('Contrato Carinho com Você - Assinatura Digital')
                    ->from(
                        config('branding.email.from_address'),
                        config('branding.email.from_name')
                    );
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send contract email', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia e-mail de notificacao de servico agendado.
     */
    public function sendServiceScheduledEmail(array $data): bool
    {
        try {
            Mail::send('emails.servico-agendado', [
                'nome' => $data['client_name'],
                'cuidador' => $data['caregiver_name'],
                'data' => $data['date'],
                'horario' => $data['time'],
            ], function ($message) use ($data) {
                $message->to($data['email'], $data['client_name'])
                    ->subject('Serviço agendado - Carinho com Você')
                    ->from(
                        config('branding.email.from_address'),
                        config('branding.email.from_name')
                    );
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send service scheduled email', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia e-mail de solicitacao de feedback.
     */
    public function sendFeedbackRequestEmail(array $data): bool
    {
        try {
            Mail::send('emails.feedback', [
                'nome' => $data['client_name'],
                'cuidador' => $data['caregiver_name'],
                'link_feedback' => $data['feedback_link'],
            ], function ($message) use ($data) {
                $message->to($data['email'], $data['client_name'])
                    ->subject('Como foi seu atendimento? - Carinho com Você')
                    ->from(
                        config('branding.email.from_address'),
                        config('branding.email.from_name')
                    );
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send feedback request email', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia e-mail de notificacao de pagamento.
     */
    public function sendPaymentNotificationEmail(array $data): bool
    {
        try {
            $template = $data['status'] === 'received'
                ? 'emails.pagamento-confirmado'
                : 'emails.pagamento-pendente';

            $subject = $data['status'] === 'received'
                ? 'Pagamento confirmado - Carinho com Você'
                : 'Lembrete de pagamento - Carinho com Você';

            Mail::send($template, [
                'nome' => $data['client_name'],
                'valor' => number_format($data['amount'], 2, ',', '.'),
                'vencimento' => $data['due_date'] ?? null,
                'link_pagamento' => $data['payment_link'] ?? null,
            ], function ($message) use ($data, $subject) {
                $message->to($data['email'], $data['client_name'])
                    ->subject($subject)
                    ->from(
                        config('branding.email.from_address'),
                        config('branding.email.from_name')
                    );
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send payment notification email', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia e-mail de repasse processado para cuidador.
     */
    public function sendPayoutProcessedEmail(array $data): bool
    {
        try {
            Mail::send('emails.repasse-processado', [
                'nome' => $data['caregiver_name'],
                'valor' => number_format($data['amount'], 2, ',', '.'),
                'periodo' => $data['period'],
            ], function ($message) use ($data) {
                $message->to($data['email'], $data['caregiver_name'])
                    ->subject('Repasse processado - Carinho com Você')
                    ->from(
                        config('branding.email.from_address'),
                        config('branding.email.from_name')
                    );
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send payout email', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia e-mail generico.
     */
    public function sendGenericEmail(string $to, string $name, string $subject, string $template, array $data): bool
    {
        try {
            Mail::send($template, $data, function ($message) use ($to, $name, $subject) {
                $message->to($to, $name)
                    ->subject($subject)
                    ->from(
                        config('branding.email.from_address'),
                        config('branding.email.from_name')
                    );
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send generic email', [
                'email' => $to,
                'template' => $template,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
