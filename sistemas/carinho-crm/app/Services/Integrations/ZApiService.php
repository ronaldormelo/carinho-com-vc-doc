<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

/**
 * Serviço de integração com Z-API para WhatsApp
 * Documentação: https://developer.z-api.io/
 */
class ZApiService
{
    protected string $baseUrl;
    protected string $instanceId;
    protected string $token;
    protected string $clientToken;
    protected int $timeout;

    public function __construct(
        string $baseUrl,
        ?string $instanceId,
        ?string $token,
        ?string $clientToken
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->instanceId = $instanceId ?? '';
        $this->token = $token ?? '';
        $this->clientToken = $clientToken ?? '';
        $this->timeout = config('integrations.zapi.timeout', 30);
    }

    /**
     * Verifica se a integração está habilitada
     */
    public function isEnabled(): bool
    {
        return config('integrations.zapi.enabled', false) 
            && !empty($this->instanceId) 
            && !empty($this->token);
    }

    /**
     * Obtém URL base para requisições
     */
    protected function getApiUrl(string $endpoint): string
    {
        return "{$this->baseUrl}/instances/{$this->instanceId}/token/{$this->token}/{$endpoint}";
    }

    /**
     * Faz requisição HTTP para a Z-API
     */
    protected function request(string $method, string $endpoint, array $data = [])
    {
        if (!$this->isEnabled()) {
            Log::channel('whatsapp')->warning('Z-API não está habilitada');
            return null;
        }

        $url = $this->getApiUrl($endpoint);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Client-Token' => $this->clientToken,
                    'Content-Type' => 'application/json',
                ])
                ->$method($url, $data);

            if ($response->successful()) {
                Log::channel('whatsapp')->info("Z-API {$method} {$endpoint}", [
                    'status' => $response->status(),
                ]);
                return $response->json();
            }

            Log::channel('whatsapp')->error("Z-API erro {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (RequestException $e) {
            Log::channel('whatsapp')->error("Z-API exceção {$endpoint}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verifica status da conexão WhatsApp
     */
    public function getConnectionStatus(): ?array
    {
        return $this->request('get', 'status');
    }

    /**
     * Verifica se está conectado
     */
    public function isConnected(): bool
    {
        $status = $this->getConnectionStatus();
        return $status && ($status['connected'] ?? false);
    }

    /**
     * Envia mensagem de texto
     * 
     * @param string $phone Número do telefone (formato: 5511999999999)
     * @param string $message Mensagem a ser enviada
     */
    public function sendTextMessage(string $phone, string $message): ?array
    {
        $phone = $this->formatPhone($phone);

        return $this->request('post', 'send-text', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }

    /**
     * Envia mensagem com botões
     */
    public function sendButtonMessage(string $phone, string $message, array $buttons): ?array
    {
        $phone = $this->formatPhone($phone);

        $formattedButtons = array_map(function ($button, $index) {
            return [
                'id' => $button['id'] ?? "btn_{$index}",
                'label' => $button['label'] ?? $button,
            ];
        }, $buttons, array_keys($buttons));

        return $this->request('post', 'send-button-list', [
            'phone' => $phone,
            'message' => $message,
            'buttonList' => [
                'buttons' => $formattedButtons,
            ],
        ]);
    }

    /**
     * Envia mensagem com lista de opções
     */
    public function sendListMessage(string $phone, string $message, string $buttonText, array $sections): ?array
    {
        $phone = $this->formatPhone($phone);

        return $this->request('post', 'send-option-list', [
            'phone' => $phone,
            'message' => $message,
            'optionList' => [
                'title' => 'Opções',
                'buttonLabel' => $buttonText,
                'options' => $sections,
            ],
        ]);
    }

    /**
     * Envia documento (PDF, etc)
     */
    public function sendDocument(string $phone, string $documentUrl, string $filename, ?string $caption = null): ?array
    {
        $phone = $this->formatPhone($phone);

        return $this->request('post', 'send-document', [
            'phone' => $phone,
            'document' => $documentUrl,
            'fileName' => $filename,
            'caption' => $caption,
        ]);
    }

    /**
     * Envia imagem
     */
    public function sendImage(string $phone, string $imageUrl, ?string $caption = null): ?array
    {
        $phone = $this->formatPhone($phone);

        return $this->request('post', 'send-image', [
            'phone' => $phone,
            'image' => $imageUrl,
            'caption' => $caption,
        ]);
    }

    /**
     * Envia link com preview
     */
    public function sendLink(string $phone, string $message, string $linkUrl, ?string $title = null, ?string $description = null): ?array
    {
        $phone = $this->formatPhone($phone);

        return $this->request('post', 'send-link', [
            'phone' => $phone,
            'message' => $message,
            'linkUrl' => $linkUrl,
            'title' => $title,
            'description' => $description,
        ]);
    }

    /**
     * Verifica se número é válido no WhatsApp
     */
    public function checkNumberExists(string $phone): ?bool
    {
        $phone = $this->formatPhone($phone);
        $result = $this->request('get', "phone-exists/{$phone}");
        
        return $result['exists'] ?? null;
    }

    /**
     * Obtém informações do perfil
     */
    public function getProfileInfo(string $phone): ?array
    {
        $phone = $this->formatPhone($phone);
        return $this->request('get', "profile-picture/{$phone}");
    }

    /**
     * Marca mensagem como lida
     */
    public function markAsRead(string $messageId, string $phone): ?array
    {
        return $this->request('post', 'read-message', [
            'messageId' => $messageId,
            'phone' => $this->formatPhone($phone),
        ]);
    }

    /**
     * Envia mensagem de boas-vindas para novo lead
     */
    public function sendWelcomeMessage(string $phone, string $leadName): ?array
    {
        $message = "Olá, {$leadName}! 👋\n\n";
        $message .= "Seja bem-vindo(a) à *Carinho com Você*!\n\n";
        $message .= "Somos especializados em cuidado domiciliar com cuidadores qualificados e avaliados.\n\n";
        $message .= "Em breve, um de nossos atendentes entrará em contato para entender melhor suas necessidades.\n\n";
        $message .= "Se preferir, você pode nos contar mais sobre o que precisa respondendo esta mensagem.\n\n";
        $message .= "_Atendimento rápido, transparente e com continuidade._";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia lembrete de follow-up
     */
    public function sendFollowUpReminder(string $phone, string $leadName): ?array
    {
        $message = "Olá, {$leadName}!\n\n";
        $message .= "Passando para saber se você ainda precisa de ajuda com cuidado domiciliar.\n\n";
        $message .= "Estamos à disposição para esclarecer dúvidas e apresentar nossas opções de serviço.\n\n";
        $message .= "Posso ajudar em algo?";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia confirmação de proposta enviada
     */
    public function sendProposalNotification(string $phone, string $leadName, float $price): ?array
    {
        $formattedPrice = 'R$ ' . number_format($price, 2, ',', '.');
        
        $message = "Olá, {$leadName}!\n\n";
        $message .= "📋 *Proposta Enviada*\n\n";
        $message .= "Enviamos uma proposta de serviço no valor de *{$formattedPrice}*.\n\n";
        $message .= "Confira os detalhes e, se tiver alguma dúvida, estamos aqui para ajudar!\n\n";
        $message .= "Obrigado pela confiança. 💙";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia link de aceite digital de contrato
     */
    public function sendContractSignatureLink(string $phone, string $clientName, string $signatureUrl): ?array
    {
        $message = "Olá, {$clientName}!\n\n";
        $message .= "📝 *Contrato Pronto para Assinatura*\n\n";
        $message .= "Seu contrato está pronto! Clique no link abaixo para revisar e assinar digitalmente:\n\n";
        $message .= "{$signatureUrl}\n\n";
        $message .= "O link é válido por 7 dias.\n\n";
        $message .= "Qualquer dúvida, estamos à disposição!";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Envia notificação de contrato expirand
     */
    public function sendContractExpiringNotification(string $phone, string $clientName, int $daysRemaining): ?array
    {
        $message = "Olá, {$clientName}!\n\n";
        $message .= "⏰ *Aviso de Renovação*\n\n";
        $message .= "Seu contrato conosco expira em *{$daysRemaining} dias*.\n\n";
        $message .= "Gostaríamos de saber se deseja renovar o serviço.\n\n";
        $message .= "Podemos agendar uma conversa para discutir a continuidade?";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Formata número de telefone para padrão Z-API (55DDDNumero)
     */
    protected function formatPhone(string $phone): string
    {
        // Remove caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Se não começa com 55, adiciona
        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}
