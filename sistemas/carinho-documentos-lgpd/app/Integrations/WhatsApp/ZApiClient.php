<?php

namespace App\Integrations\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cliente para integracao com Z-API (WhatsApp).
 *
 * Documentacao: https://developer.z-api.io/
 *
 * Endpoints principais:
 * - POST /instances/{instance}/token/{token}/send-text
 * - POST /instances/{instance}/token/{token}/send-document
 * - POST /instances/{instance}/token/{token}/send-link
 *
 * Utilizado para:
 * - Enviar links de assinatura de contratos
 * - Notificar sobre documentos prontos
 * - Enviar codigos OTP para assinatura
 */
class ZApiClient
{
    /**
     * Envia mensagem de texto.
     */
    public function sendTextMessage(string $phone, string $message): array
    {
        return $this->request('send-text', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
    }

    /**
     * Envia link de assinatura de contrato.
     */
    public function sendContractLink(string $phone, string $contractUrl, string $recipientName): array
    {
        $message = $this->formatContractMessage($recipientName);

        return $this->request('send-link', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
            'linkUrl' => $contractUrl,
            'title' => 'Assinar Contrato - Carinho com Você',
        ]);
    }

    /**
     * Envia codigo OTP para assinatura.
     */
    public function sendOtpCode(string $phone, string $code): array
    {
        $message = $this->formatOtpMessage($code);

        return $this->request('send-text', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
    }

    /**
     * Envia notificacao de documento assinado.
     */
    public function sendSignatureConfirmation(string $phone, string $documentType, string $downloadUrl): array
    {
        $message = $this->formatSignatureConfirmationMessage($documentType);

        return $this->request('send-link', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
            'linkUrl' => $downloadUrl,
            'title' => 'Documento Assinado - Carinho com Você',
        ]);
    }

    /**
     * Envia documento em PDF.
     */
    public function sendDocument(string $phone, string $documentUrl, string $fileName): array
    {
        return $this->request('send-document', [
            'phone' => $this->normalizePhone($phone),
            'document' => $documentUrl,
            'fileName' => $fileName,
        ]);
    }

    /**
     * Envia notificacao de solicitacao LGPD.
     */
    public function sendDataRequestNotification(string $phone, string $requestType, string $status): array
    {
        $message = $this->formatDataRequestMessage($requestType, $status);

        return $this->request('send-text', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
    }

    /**
     * Envia link para termos e politica de privacidade.
     */
    public function sendTermsLink(string $phone): array
    {
        $message = "Ola! Aqui estao os documentos importantes da Carinho com Voce:\n\n"
            . "Acesse os links para conhecer nossos Termos de Uso e Politica de Privacidade.";

        return $this->request('send-link', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
            'linkUrl' => config('branding.domain') . '/termos',
            'title' => 'Termos e Privacidade - Carinho com Você',
        ]);
    }

    /**
     * Formata mensagem de contrato.
     */
    private function formatContractMessage(string $recipientName): string
    {
        return "Ola, {$recipientName}!\n\n"
            . "Seu contrato com a Carinho com Voce esta pronto para assinatura.\n\n"
            . "Clique no link abaixo para revisar e assinar digitalmente.\n\n"
            . "O link e valido por 72 horas.\n\n"
            . "Em caso de duvidas, estamos a disposicao.";
    }

    /**
     * Formata mensagem de OTP.
     */
    private function formatOtpMessage(string $code): string
    {
        return "Carinho com Voce\n\n"
            . "Seu codigo de verificacao para assinatura digital e:\n\n"
            . "*{$code}*\n\n"
            . "Este codigo e valido por 10 minutos.\n\n"
            . "Se voce nao solicitou este codigo, ignore esta mensagem.";
    }

    /**
     * Formata mensagem de confirmacao de assinatura.
     */
    private function formatSignatureConfirmationMessage(string $documentType): string
    {
        return "Carinho com Voce\n\n"
            . "Seu documento ({$documentType}) foi assinado com sucesso!\n\n"
            . "Clique no link abaixo para baixar uma copia.\n\n"
            . "Obrigado pela confianca!";
    }

    /**
     * Formata mensagem de solicitacao LGPD.
     */
    private function formatDataRequestMessage(string $requestType, string $status): string
    {
        $typeLabels = [
            'export' => 'exportacao de dados',
            'delete' => 'exclusao de dados',
            'update' => 'atualizacao de dados',
        ];

        $statusLabels = [
            'open' => 'foi recebida e sera processada em breve',
            'in_progress' => 'esta sendo processada',
            'done' => 'foi concluida com sucesso',
            'rejected' => 'nao pode ser atendida',
        ];

        $type = $typeLabels[$requestType] ?? $requestType;
        $statusText = $statusLabels[$status] ?? $status;

        return "Carinho com Voce\n\n"
            . "Sua solicitacao de {$type} {$statusText}.\n\n"
            . "Em caso de duvidas, entre em contato pelo e-mail " . config('branding.email.privacy');
    }

    /**
     * Verifica status da instancia.
     */
    public function getInstanceStatus(): array
    {
        return $this->request('status', [], 'GET');
    }

    /**
     * Realiza requisicao para a API.
     */
    private function request(string $path, array $payload = [], string $method = 'POST'): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->connectTimeout((int) config('integrations.whatsapp.connect_timeout', 3))
                ->timeout((int) config('integrations.whatsapp.timeout', 10));

            if ($method === 'GET') {
                $response = $request->get($this->endpoint($path));
            } else {
                $response = $request->post($this->endpoint($path), $payload);
            }

            $result = [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ];

            if (!$response->successful()) {
                Log::warning('Z-API request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Z-API request error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 0,
                'ok' => false,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Monta URL do endpoint.
     */
    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.whatsapp.base_url'), '/');
        $instanceId = trim((string) config('integrations.whatsapp.instance_id'), '/');
        $token = trim((string) config('integrations.whatsapp.token'), '/');

        return "{$baseUrl}/instances/{$instanceId}/token/{$token}/{$path}";
    }

    /**
     * Retorna headers da requisicao.
     */
    private function headers(): array
    {
        $clientToken = config('integrations.whatsapp.client_token');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($clientToken) {
            $headers['client-token'] = $clientToken;
        }

        return $headers;
    }

    /**
     * Normaliza numero de telefone.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        // Adiciona codigo do pais se necessario
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '55' . substr($digits, 1);
        } elseif (strlen($digits) === 10 || strlen($digits) === 11) {
            if (!str_starts_with($digits, '55')) {
                $digits = '55' . $digits;
            }
        }

        return $digits ?: Str::of($phone)->trim()->toString();
    }
}
