<?php

namespace App\Services;

use App\Models\SyncJob;
use App\Services\Integrations\Crm\CrmClient;
use App\Services\Integrations\Operacao\OperacaoClient;
use App\Services\Integrations\Financeiro\FinanceiroClient;
use App\Services\Integrations\Cuidadores\CuidadoresClient;
use Illuminate\Support\Facades\Log;

/**
 * Servico de sincronizacao entre sistemas.
 *
 * Responsavel por manter dados consistentes entre:
 * - CRM <-> Operacao (agenda e alocacao)
 * - Operacao <-> Financeiro (cobranca e repasse)
 * - CRM <-> Financeiro (contratos e pagamentos)
 * - Cuidadores <-> CRM (dados e avaliacoes)
 */
class SyncService
{
    public function __construct(
        private CrmClient $crm,
        private OperacaoClient $operacao,
        private FinanceiroClient $financeiro,
        private CuidadoresClient $cuidadores
    ) {}

    /**
     * Sincroniza dados do CRM para Operacao.
     *
     * Fluxo: Cliente convertido -> cria agenda na operacao
     */
    public function syncCrmToOperacao(): SyncJob
    {
        $job = SyncJob::queue(SyncJob::TYPE_CRM_OPERACAO);

        try {
            $job->start();

            // Busca clientes com servicos agendados pendentes de sincronizacao
            // Este endpoint retornaria clientes que precisam ter agendamentos criados
            $response = $this->crm->get('/api/v1/sync/pending-schedules');

            if (!$response['ok']) {
                throw new \Exception('Failed to fetch pending schedules from CRM');
            }

            $items = $response['body']['data'] ?? [];
            $synced = 0;

            foreach ($items as $item) {
                $result = $this->operacao->createSchedule([
                    'client_id' => $item['client_id'],
                    'crm_contract_id' => $item['contract_id'],
                    'service_type' => $item['service_type'],
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                    'preferences' => $item['preferences'] ?? [],
                ]);

                if ($result['ok']) {
                    // Confirma sincronizacao no CRM
                    $this->crm->post("/api/v1/sync/confirm/{$item['id']}", [
                        'operacao_schedule_id' => $result['body']['id'],
                    ]);

                    $synced++;
                }
            }

            Log::info('CRM to Operacao sync completed', [
                'total' => count($items),
                'synced' => $synced,
            ]);

            $job->complete();
        } catch (\Throwable $e) {
            Log::error('CRM to Operacao sync failed', [
                'error' => $e->getMessage(),
            ]);

            $job->fail();
        }

        return $job;
    }

    /**
     * Sincroniza dados da Operacao para Financeiro.
     *
     * Fluxo: Servico executado -> gera fatura e calcula repasse
     */
    public function syncOperacaoToFinanceiro(): SyncJob
    {
        $job = SyncJob::queue(SyncJob::TYPE_OPERACAO_FINANCEIRO);

        try {
            $job->start();

            // Busca servicos finalizados pendentes de faturamento
            $startDate = now()->subDays(7)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            $response = $this->operacao->getCompletedServices($startDate, $endDate);

            if (!$response['ok']) {
                throw new \Exception('Failed to fetch completed services');
            }

            $services = $response['body']['data'] ?? [];
            $synced = 0;

            foreach ($services as $service) {
                // Verifica se ja foi faturado
                if ($service['billed'] ?? false) {
                    continue;
                }

                // Cria fatura no financeiro
                $invoiceResult = $this->financeiro->createInvoice([
                    'client_id' => $service['client_id'],
                    'operacao_service_id' => $service['id'],
                    'items' => $this->buildInvoiceItems($service),
                    'due_date' => now()->addDays(3)->format('Y-m-d'),
                ]);

                if ($invoiceResult['ok']) {
                    // Marca servico como faturado na operacao
                    $this->operacao->put("/api/v1/services/{$service['id']}/billed", [
                        'invoice_id' => $invoiceResult['body']['id'],
                    ]);

                    $synced++;
                }
            }

            Log::info('Operacao to Financeiro sync completed', [
                'total' => count($services),
                'synced' => $synced,
            ]);

            $job->complete();
        } catch (\Throwable $e) {
            Log::error('Operacao to Financeiro sync failed', [
                'error' => $e->getMessage(),
            ]);

            $job->fail();
        }

        return $job;
    }

    /**
     * Sincroniza dados do CRM para Financeiro.
     *
     * Fluxo: Contrato assinado -> configura cobranca recorrente
     */
    public function syncCrmToFinanceiro(): SyncJob
    {
        $job = SyncJob::queue(SyncJob::TYPE_CRM_FINANCEIRO);

        try {
            $job->start();

            // Busca contratos assinados pendentes de configuracao financeira
            $response = $this->crm->get('/api/v1/sync/pending-billing-setup');

            if (!$response['ok']) {
                throw new \Exception('Failed to fetch pending billing setup');
            }

            $contracts = $response['body']['data'] ?? [];
            $synced = 0;

            foreach ($contracts as $contract) {
                // Configura cliente no financeiro
                $result = $this->financeiro->post('/api/billing/setup', [
                    'crm_client_id' => $contract['client_id'],
                    'crm_contract_id' => $contract['id'],
                    'billing_type' => $contract['billing_type'],
                    'amount' => $contract['value'],
                    'payment_method' => $contract['payment_method'],
                ]);

                if ($result['ok']) {
                    // Confirma setup no CRM
                    $this->crm->post("/api/v1/contracts/{$contract['id']}/billing-setup", [
                        'financeiro_id' => $result['body']['id'],
                    ]);

                    $synced++;
                }
            }

            Log::info('CRM to Financeiro sync completed', [
                'total' => count($contracts),
                'synced' => $synced,
            ]);

            $job->complete();
        } catch (\Throwable $e) {
            Log::error('CRM to Financeiro sync failed', [
                'error' => $e->getMessage(),
            ]);

            $job->fail();
        }

        return $job;
    }

    /**
     * Sincroniza dados de Cuidadores para CRM.
     *
     * Fluxo: Atualizacoes de cuidador -> atualiza registros no CRM
     */
    public function syncCuidadoresToCrm(): SyncJob
    {
        $job = SyncJob::queue(SyncJob::TYPE_CUIDADORES_CRM);

        try {
            $job->start();

            // Busca cuidadores com atualizacoes pendentes
            $response = $this->cuidadores->get('/api/v1/sync/pending-updates');

            if (!$response['ok']) {
                throw new \Exception('Failed to fetch caregiver updates');
            }

            $updates = $response['body']['data'] ?? [];
            $synced = 0;

            foreach ($updates as $update) {
                $result = $this->crm->dispatchEvent('caregiver.updated', [
                    'caregiver_id' => $update['caregiver_id'],
                    'changes' => $update['changes'],
                    'timestamp' => $update['updated_at'],
                ]);

                if ($result['ok']) {
                    // Confirma sincronizacao
                    $this->cuidadores->post("/api/v1/sync/confirm/{$update['id']}");
                    $synced++;
                }
            }

            Log::info('Cuidadores to CRM sync completed', [
                'total' => count($updates),
                'synced' => $synced,
            ]);

            $job->complete();
        } catch (\Throwable $e) {
            Log::error('Cuidadores to CRM sync failed', [
                'error' => $e->getMessage(),
            ]);

            $job->fail();
        }

        return $job;
    }

    /**
     * Executa sincronizacao completa.
     */
    public function fullSync(): array
    {
        $results = [];

        $results['crm_operacao'] = $this->syncCrmToOperacao();
        $results['operacao_financeiro'] = $this->syncOperacaoToFinanceiro();
        $results['crm_financeiro'] = $this->syncCrmToFinanceiro();
        $results['cuidadores_crm'] = $this->syncCuidadoresToCrm();

        return $results;
    }

    /**
     * Monta itens da fatura baseado no servico.
     */
    private function buildInvoiceItems(array $service): array
    {
        $items = [];

        // Item principal
        $items[] = [
            'description' => "Serviço de cuidador - {$service['service_type']}",
            'hours' => $service['hours'] ?? 0,
            'unit_price' => $service['hourly_rate'] ?? 0,
            'total' => $service['total'] ?? 0,
        ];

        // Adicionais
        if (!empty($service['extras'])) {
            foreach ($service['extras'] as $extra) {
                $items[] = [
                    'description' => $extra['description'],
                    'hours' => 0,
                    'unit_price' => $extra['value'],
                    'total' => $extra['value'],
                ];
            }
        }

        return $items;
    }

    /**
     * Estatisticas de sincronizacao.
     */
    public function getStats(): array
    {
        return SyncJob::getStats();
    }
}
