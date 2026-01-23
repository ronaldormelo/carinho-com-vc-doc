<?php

namespace App\Services\Integrations\Financeiro;

use App\Services\Integrations\BaseClient;

/**
 * Cliente para integracao com o sistema Financeiro (financeiro.carinho.com.vc).
 *
 * Responsavel por:
 * - Criacao de faturas
 * - Processamento de pagamentos
 * - Calculo de repasses para cuidadores
 * - Conciliacao financeira
 */
class FinanceiroClient extends BaseClient
{
    protected string $configKey = 'financeiro';

    /*
    |--------------------------------------------------------------------------
    | Faturas
    |--------------------------------------------------------------------------
    */

    /**
     * Cria nova fatura.
     */
    public function createInvoice(array $data): array
    {
        return $this->post('/api/invoices', $data);
    }

    /**
     * Busca fatura por ID.
     */
    public function getInvoice(int $invoiceId): array
    {
        return $this->get("/api/invoices/{$invoiceId}");
    }

    /**
     * Lista faturas do cliente.
     */
    public function getClientInvoices(int $clientId): array
    {
        return $this->get('/api/invoices', ['client_id' => $clientId]);
    }

    /**
     * Adiciona item a fatura.
     */
    public function addInvoiceItem(int $invoiceId, array $data): array
    {
        return $this->post("/api/invoices/{$invoiceId}/items", $data);
    }

    /**
     * Cancela fatura.
     */
    public function cancelInvoice(int $invoiceId, string $reason): array
    {
        return $this->post("/api/invoices/{$invoiceId}/cancel", [
            'reason' => $reason,
        ]);
    }

    /**
     * Lista faturas vencidas.
     */
    public function getOverdueInvoices(): array
    {
        return $this->get('/api/invoices', ['status' => 'overdue']);
    }

    /*
    |--------------------------------------------------------------------------
    | Pagamentos
    |--------------------------------------------------------------------------
    */

    /**
     * Cria pagamento para fatura.
     */
    public function createPayment(array $data): array
    {
        return $this->post('/api/payments', $data);
    }

    /**
     * Gera link de pagamento PIX/Boleto.
     */
    public function generatePaymentLink(int $invoiceId, string $method = 'pix'): array
    {
        return $this->post("/api/payments/invoice/{$invoiceId}/generate-link", [
            'method' => $method,
        ]);
    }

    /**
     * Busca pagamento por ID.
     */
    public function getPayment(int $paymentId): array
    {
        return $this->get("/api/payments/{$paymentId}");
    }

    /**
     * Processa reembolso.
     */
    public function processRefund(int $paymentId, array $data): array
    {
        return $this->post("/api/payments/{$paymentId}/refund", [
            'amount' => $data['amount'] ?? null, // null = reembolso total
            'reason' => $data['reason'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Repasses
    |--------------------------------------------------------------------------
    */

    /**
     * Cria repasse para cuidador.
     */
    public function createPayout(array $data): array
    {
        return $this->post('/api/payouts', $data);
    }

    /**
     * Gera repasses do periodo.
     */
    public function generatePeriodPayouts(string $startDate, string $endDate): array
    {
        return $this->post('/api/payouts/generate', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Busca repasse por ID.
     */
    public function getPayout(int $payoutId): array
    {
        return $this->get("/api/payouts/{$payoutId}");
    }

    /**
     * Processa transferencia do repasse.
     */
    public function processPayout(int $payoutId): array
    {
        return $this->post("/api/payouts/{$payoutId}/process");
    }

    /**
     * Lista repasses do cuidador.
     */
    public function getCaregiverPayouts(int $caregiverId): array
    {
        return $this->get('/api/payouts', ['caregiver_id' => $caregiverId]);
    }

    /*
    |--------------------------------------------------------------------------
    | Precificacao
    |--------------------------------------------------------------------------
    */

    /**
     * Calcula preco do servico.
     */
    public function calculatePrice(array $data): array
    {
        return $this->post('/api/pricing/calculate', $data);
    }

    /**
     * Simula precos para diferentes configuracoes.
     */
    public function simulatePricing(array $scenarios): array
    {
        return $this->post('/api/pricing/simulate', [
            'scenarios' => $scenarios,
        ]);
    }

    /**
     * Busca politica de cancelamento.
     */
    public function getCancellationPolicy(): array
    {
        return $this->getCached('/api/pricing/cancellation-policy');
    }

    /**
     * Busca configuracao de comissoes.
     */
    public function getCommissionConfig(): array
    {
        return $this->getCached('/api/pricing/commission-config');
    }

    /*
    |--------------------------------------------------------------------------
    | Conciliacao
    |--------------------------------------------------------------------------
    */

    /**
     * Processa conciliacao do periodo.
     */
    public function processReconciliation(string $month): array
    {
        return $this->post('/api/reconciliation/process', [
            'month' => $month,
        ]);
    }

    /**
     * Busca fluxo de caixa.
     */
    public function getCashFlow(string $startDate, string $endDate): array
    {
        return $this->get('/api/reconciliation/cash-flow', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Busca indicadores financeiros.
     */
    public function getFinancialIndicators(): array
    {
        return $this->get('/api/reconciliation/indicators');
    }

    /*
    |--------------------------------------------------------------------------
    | Configuracoes
    |--------------------------------------------------------------------------
    */

    /**
     * Busca configuracao por chave.
     */
    public function getSetting(string $key): array
    {
        return $this->getCached("/api/settings/{$key}");
    }

    /**
     * Busca configuracoes de uma categoria.
     */
    public function getSettingsByCategory(string $category): array
    {
        return $this->getCached("/api/settings/category/{$category}");
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Dispara evento para Financeiro.
     */
    public function dispatchEvent(string $eventType, array $payload): array
    {
        return $this->post('/api/webhooks/events', [
            'event_type' => $eventType,
            'payload' => $payload,
            'source' => 'integracoes',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
