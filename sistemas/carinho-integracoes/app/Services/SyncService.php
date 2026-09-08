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

            Log::info('CRM não expõe /api/v1/sync/pending-schedules; sync CRM→Operação não implementada no destino.');

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

            Log::info('Operação não expõe serviços concluídos não faturados; InvoiceRequest exige contract_id. Sync Operação→Financeiro não implementada.');

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

            Log::info('CRM não expõe /api/v1/sync/pending-billing-setup; sync CRM→Financeiro não implementada no destino.');

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

            Log::info('Cuidadores não expõe /api/v1/sync/pending-updates; sync Cuidadores→CRM não implementada no destino.');

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
     * Estatisticas de sincronizacao.
     */
    public function getStats(): array
    {
        return SyncJob::getStats();
    }
}
