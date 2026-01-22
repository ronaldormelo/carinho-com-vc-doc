<?php

namespace App\Services;

use App\Integrations\WhatsApp\ZApiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service para envio de notificacoes.
 */
class NotificationService
{
    public function __construct(
        private ZApiClient $whatsApp
    ) {}

    /**
     * Envia notificacao de contrato pronto.
     */
    public function notifyContractReady(
        string $phone,
        string $email,
        string $recipientName,
        string $signatureUrl
    ): array {
        $results = [];

        // WhatsApp
        if ($phone) {
            $results['whatsapp'] = $this->whatsApp->sendContractLink($phone, $signatureUrl, $recipientName);
        }

        // Email
        if ($email) {
            $results['email'] = $this->sendContractReadyEmail($email, $recipientName, $signatureUrl);
        }

        return $results;
    }

    /**
     * Envia notificacao de contrato assinado.
     */
    public function notifyContractSigned(
        string $phone,
        string $email,
        string $documentType,
        string $downloadUrl
    ): array {
        $results = [];

        // WhatsApp
        if ($phone) {
            $results['whatsapp'] = $this->whatsApp->sendSignatureConfirmation($phone, $documentType, $downloadUrl);
        }

        // Email
        if ($email) {
            $results['email'] = $this->sendContractSignedEmail($email, $documentType, $downloadUrl);
        }

        return $results;
    }

    /**
     * Envia codigo OTP.
     */
    public function sendOtp(string $phone, string $code): array
    {
        return $this->whatsApp->sendOtpCode($phone, $code);
    }

    /**
     * Envia notificacao de documento.
     */
    public function notifyDocumentUploaded(
        string $phone,
        string $recipientName,
        string $documentType
    ): array {
        $message = "Ola, {$recipientName}!\n\n"
            . "Seu documento ({$documentType}) foi recebido com sucesso.\n\n"
            . "Em breve sera processado e voce sera notificado.\n\n"
            . "Equipe Carinho com Voce";

        return $this->whatsApp->sendTextMessage($phone, $message);
    }

    /**
     * Envia email de contrato pronto.
     */
    private function sendContractReadyEmail(string $email, string $recipientName, string $signatureUrl): array
    {
        try {
            Mail::send('emails.contrato_pronto', [
                'recipientName' => $recipientName,
                'signatureUrl' => $signatureUrl,
                'brandName' => config('branding.name', 'Carinho com Voce'),
            ], function ($message) use ($email, $recipientName) {
                $message->to($email, $recipientName)
                    ->from(config('branding.email.from', 'documentos@carinho.com.vc'), config('branding.name'))
                    ->subject('Seu contrato está pronto para assinatura - Carinho com Você');
            });

            return ['ok' => true];
        } catch (\Throwable $e) {
            Log::error('Failed to send contract ready email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envia email de contrato assinado.
     */
    private function sendContractSignedEmail(string $email, string $documentType, string $downloadUrl): array
    {
        try {
            Mail::send('emails.contrato_assinado', [
                'documentType' => $documentType,
                'downloadUrl' => $downloadUrl,
                'brandName' => config('branding.name', 'Carinho com Voce'),
            ], function ($message) use ($email) {
                $message->to($email)
                    ->from(config('branding.email.from', 'documentos@carinho.com.vc'), config('branding.name'))
                    ->subject('Contrato assinado com sucesso - Carinho com Você');
            });

            return ['ok' => true];
        } catch (\Throwable $e) {
            Log::error('Failed to send contract signed email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
