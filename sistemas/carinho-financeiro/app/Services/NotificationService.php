<?php

namespace App\Services;

use App\Integrations\WhatsApp\ZApiClient;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Notificações Financeiras.
 *
 * Envia notificações via WhatsApp (Z-API) para:
 * - Clientes: faturas, pagamentos, lembretes
 * - Cuidadores: repasses processados
 */
class NotificationService
{
    public function __construct(
        protected ZApiClient $whatsAppClient
    ) {}

    /**
     * Notifica cliente sobre nova fatura.
     */
    public function notifyInvoiceCreated(Invoice $invoice, string $phone, ?string $paymentLink = null): bool
    {
        $message = $this->buildMessage('invoice_created', [
            'client_name' => $this->getClientName($invoice->client_id),
            'amount' => $this->formatMoney($invoice->total_amount),
            'due_date' => $invoice->due_date?->format('d/m/Y'),
            'link' => $paymentLink ?? 'Acesse seu painel para visualizar',
        ]);

        return $this->sendWhatsAppMessage($phone, $message);
    }

    /**
     * Notifica cliente sobre pagamento confirmado.
     */
    public function notifyPaymentConfirmed(Payment $payment, string $phone): bool
    {
        $message = $this->buildMessage('payment_confirmed', [
            'amount' => $this->formatMoney($payment->amount),
            'method' => $payment->method?->label ?? 'N/A',
        ]);

        return $this->sendWhatsAppMessage($phone, $message);
    }

    /**
     * Envia lembrete de vencimento.
     */
    public function sendDueReminder(Invoice $invoice, string $phone, int $daysUntilDue): bool
    {
        $message = $this->buildMessage('payment_reminder', [
            'client_name' => $this->getClientName($invoice->client_id),
            'days' => $daysUntilDue,
            'amount' => $this->formatMoney($invoice->total_amount),
            'due_date' => $invoice->due_date?->format('d/m/Y'),
        ]);

        return $this->sendWhatsAppMessage($phone, $message);
    }

    /**
     * Notifica sobre fatura vencida.
     */
    public function notifyInvoiceOverdue(Invoice $invoice, string $phone): bool
    {
        $message = $this->buildMessage('payment_overdue', [
            'client_name' => $this->getClientName($invoice->client_id),
            'amount' => $this->formatMoney($invoice->total_with_fees),
            'original_amount' => $this->formatMoney($invoice->total_amount),
            'due_date' => $invoice->due_date?->format('d/m/Y'),
        ]);

        return $this->sendWhatsAppMessage($phone, $message);
    }

    /**
     * Notifica cuidador sobre repasse processado.
     */
    public function notifyPayoutProcessed(Payout $payout, string $phone): bool
    {
        $message = $this->buildMessage('payout_processed', [
            'amount' => $this->formatMoney($payout->net_amount),
            'period' => $payout->period_start?->format('d/m') . ' a ' . $payout->period_end?->format('d/m/Y'),
            'items_count' => $payout->items()->count(),
        ]);

        return $this->sendWhatsAppMessage($phone, $message);
    }

    /**
     * Notifica sobre cancelamento e reembolso.
     */
    public function notifyCancellationProcessed(Invoice $invoice, string $phone, float $refundAmount): bool
    {
        $message = $this->buildMessage('cancellation_processed', [
            'client_name' => $this->getClientName($invoice->client_id),
            'amount' => $this->formatMoney($refundAmount),
        ]);

        return $this->sendWhatsAppMessage($phone, $message);
    }

    /**
     * Envia link de pagamento PIX.
     */
    public function sendPixPaymentLink(Payment $payment, string $phone): bool
    {
        $invoice = $payment->invoice;
        
        $message = "Olá! Segue o PIX para pagamento:\n\n";
        $message .= "Valor: {$this->formatMoney($payment->amount)}\n";
        $message .= "Vencimento: {$invoice->due_date?->format('d/m/Y')}\n\n";
        $message .= "Copie o código PIX abaixo:\n";
        $message .= $payment->pix_code ?? 'Código não disponível';

        $result = $this->sendWhatsAppMessage($phone, $message);

        // Se tiver QR Code, envia também
        if ($result && $payment->pix_qrcode_url) {
            $this->whatsAppClient->sendMediaMessage(
                $phone,
                $payment->pix_qrcode_url,
                'QR Code para pagamento'
            );
        }

        return $result;
    }

    /**
     * Envia link de boleto.
     */
    public function sendBoletoLink(Payment $payment, string $phone): bool
    {
        $invoice = $payment->invoice;
        
        $message = "Olá! Segue o boleto para pagamento:\n\n";
        $message .= "Valor: {$this->formatMoney($payment->amount)}\n";
        $message .= "Vencimento: {$invoice->due_date?->format('d/m/Y')}\n\n";
        $message .= "Link do boleto:\n{$payment->boleto_url}\n\n";
        
        if ($payment->boleto_barcode) {
            $message .= "Código de barras:\n{$payment->boleto_barcode}";
        }

        return $this->sendWhatsAppMessage($phone, $message);
    }

    /**
     * Constrói mensagem a partir de template.
     */
    protected function buildMessage(string $templateKey, array $params): string
    {
        $template = config("branding.messages.{$templateKey}");

        if (!$template) {
            $template = $this->getDefaultTemplate($templateKey);
        }

        foreach ($params as $key => $value) {
            $template = str_replace("{{$key}}", $value ?? '', $template);
        }

        return $template;
    }

    /**
     * Obtém template padrão.
     */
    protected function getDefaultTemplate(string $key): string
    {
        $templates = [
            'invoice_created' => "Olá {client_name}!\n\nSua fatura de {amount} foi gerada.\nVencimento: {due_date}\n\n{link}",
            'payment_confirmed' => "Pagamento confirmado!\n\nValor: {amount}\nForma: {method}\n\nObrigado pela confiança!",
            'payment_reminder' => "Olá {client_name}!\n\nSua fatura de {amount} vence em {days} dias ({due_date}).\nEvite juros pagando em dia!",
            'payment_overdue' => "Olá {client_name}!\n\nSua fatura está em atraso.\nValor atualizado: {amount}\n\nRegularize para continuar usando nossos serviços.",
            'payout_processed' => "Repasse realizado!\n\nValor: {amount}\nPeríodo: {period}\nServiços: {items_count}\n\nO valor já está disponível em sua conta.",
            'cancellation_processed' => "Olá {client_name}!\n\nSeu cancelamento foi processado.\nReembolso de {amount} será creditado em até 5 dias úteis.",
        ];

        return $templates[$key] ?? '';
    }

    /**
     * Envia mensagem via WhatsApp.
     */
    protected function sendWhatsAppMessage(string $phone, string $message): bool
    {
        try {
            $result = $this->whatsAppClient->sendTextMessage($phone, $message);

            if (!$result['ok']) {
                Log::warning('Falha ao enviar WhatsApp', [
                    'phone' => $this->maskPhone($phone),
                    'error' => $result['body'] ?? 'Unknown error',
                ]);
                return false;
            }

            Log::info('WhatsApp enviado', [
                'phone' => $this->maskPhone($phone),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erro ao enviar WhatsApp', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtém nome do cliente (placeholder - seria integrado com CRM).
     */
    protected function getClientName(int $clientId): string
    {
        // Aqui seria integrado com o sistema CRM
        return 'Cliente';
    }

    /**
     * Formata valor monetário.
     */
    protected function formatMoney(float $amount): string
    {
        return 'R$ ' . number_format($amount, 2, ',', '.');
    }

    /**
     * Mascara telefone para logs.
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return '****';
        }
        return substr($phone, 0, 4) . '****' . substr($phone, -4);
    }
}
