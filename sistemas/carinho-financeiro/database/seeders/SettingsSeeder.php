<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\SettingCategory;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed das configurações padrão do sistema financeiro.
     */
    public function run(): void
    {
        $this->seedPaymentSettings();
        $this->seedCancellationSettings();
        $this->seedCommissionSettings();
        $this->seedPricingSettings();
        $this->seedMarginSettings();
        $this->seedPayoutSettings();
        $this->seedFiscalSettings();
        $this->seedLimitsSettings();
        $this->seedBonusSettings();
    }

    /**
     * Configurações de Pagamento.
     */
    protected function seedPaymentSettings(): void
    {
        $categoryId = SettingCategory::PAYMENT;

        $settings = [
            [
                'key' => Setting::KEY_PAYMENT_ADVANCE_HOURS,
                'name' => 'Antecedência do Pagamento',
                'description' => 'Horas de antecedência mínima para pagamento antes do serviço',
                'value' => '24',
                'default_value' => '24',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => 'horas',
                'validation_rules' => ['min' => 1, 'max' => 72],
                'is_public' => true,
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_PAYMENT_GRACE_DAYS,
                'name' => 'Dias de Tolerância',
                'description' => 'Dias de tolerância após vencimento antes de aplicar juros',
                'value' => '0',
                'default_value' => '0',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => 'dias',
                'validation_rules' => ['min' => 0, 'max' => 7],
                'display_order' => 2,
            ],
            [
                'key' => Setting::KEY_PAYMENT_LATE_FEE_DAILY,
                'name' => 'Juros por Dia',
                'description' => 'Percentual de juros por dia de atraso',
                'value' => '0.033',
                'default_value' => '0.033',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 1],
                'display_order' => 3,
            ],
            [
                'key' => Setting::KEY_PAYMENT_LATE_PENALTY,
                'name' => 'Multa por Atraso',
                'description' => 'Percentual de multa fixa por atraso',
                'value' => '2',
                'default_value' => '2',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 10],
                'display_order' => 4,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Configurações de Cancelamento.
     */
    protected function seedCancellationSettings(): void
    {
        $categoryId = SettingCategory::CANCELLATION;

        $settings = [
            [
                'key' => Setting::KEY_CANCEL_FREE_HOURS,
                'name' => 'Cancelamento Gratuito',
                'description' => 'Horas de antecedência para cancelamento sem custo',
                'value' => '24',
                'default_value' => '24',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => 'horas',
                'validation_rules' => ['min' => 1, 'max' => 72],
                'is_public' => true,
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_CANCEL_PARTIAL_HOURS,
                'name' => 'Limite Reembolso Parcial',
                'description' => 'Horas antes do serviço para reembolso parcial',
                'value' => '12',
                'default_value' => '12',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => 'horas',
                'validation_rules' => ['min' => 1, 'max' => 48],
                'is_public' => true,
                'display_order' => 2,
            ],
            [
                'key' => Setting::KEY_CANCEL_PARTIAL_PERCENT,
                'name' => 'Percentual Reembolso Parcial',
                'description' => 'Percentual do valor a ser reembolsado no cancelamento parcial',
                'value' => '50',
                'default_value' => '50',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => '%',
                'validation_rules' => ['min' => 10, 'max' => 90],
                'is_public' => true,
                'display_order' => 3,
            ],
            [
                'key' => Setting::KEY_CANCEL_NO_REFUND_HOURS,
                'name' => 'Sem Reembolso',
                'description' => 'Horas antes do serviço sem direito a reembolso',
                'value' => '6',
                'default_value' => '6',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => 'horas',
                'validation_rules' => ['min' => 1, 'max' => 24],
                'is_public' => true,
                'display_order' => 4,
            ],
            [
                'key' => Setting::KEY_CANCEL_ADMIN_FEE,
                'name' => 'Taxa Administrativa',
                'description' => 'Taxa administrativa sobre reembolsos',
                'value' => '5',
                'default_value' => '5',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 20],
                'is_public' => true,
                'display_order' => 5,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Configurações de Comissão.
     */
    protected function seedCommissionSettings(): void
    {
        $categoryId = SettingCategory::COMMISSION;

        $settings = [
            [
                'key' => Setting::KEY_COMMISSION_DEFAULT,
                'name' => 'Comissão Padrão do Cuidador',
                'description' => 'Percentual padrão que o cuidador recebe',
                'value' => '70',
                'default_value' => '70',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 50, 'max' => 90],
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_COMMISSION_HORISTA,
                'name' => 'Comissão Horista',
                'description' => 'Percentual do cuidador para serviços por hora',
                'value' => '70',
                'default_value' => '70',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 50, 'max' => 90],
                'display_order' => 2,
            ],
            [
                'key' => Setting::KEY_COMMISSION_DIARIO,
                'name' => 'Comissão Diária',
                'description' => 'Percentual do cuidador para serviços diários',
                'value' => '72',
                'default_value' => '72',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 50, 'max' => 90],
                'display_order' => 3,
            ],
            [
                'key' => Setting::KEY_COMMISSION_MENSAL,
                'name' => 'Comissão Mensal',
                'description' => 'Percentual do cuidador para contratos mensais',
                'value' => '75',
                'default_value' => '75',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 50, 'max' => 90],
                'display_order' => 4,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Configurações de Precificação.
     */
    protected function seedPricingSettings(): void
    {
        $categoryId = SettingCategory::PRICING;

        $settings = [
            [
                'key' => Setting::KEY_PRICING_MIN_HOURLY,
                'name' => 'Preço Mínimo por Hora',
                'description' => 'Valor mínimo viável por hora de serviço',
                'value' => '35',
                'default_value' => '35',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 20, 'max' => 100],
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_PRICING_HORISTA_HOUR,
                'name' => 'Preço Hora (Horista)',
                'description' => 'Valor padrão por hora para serviço horista',
                'value' => '50',
                'default_value' => '50',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 30, 'max' => 200],
                'is_public' => true,
                'display_order' => 2,
            ],
            [
                'key' => Setting::KEY_PRICING_HORISTA_MIN_HOURS,
                'name' => 'Mínimo de Horas (Horista)',
                'description' => 'Quantidade mínima de horas por atendimento',
                'value' => '4',
                'default_value' => '4',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => 'horas',
                'validation_rules' => ['min' => 1, 'max' => 12],
                'is_public' => true,
                'display_order' => 3,
            ],
            [
                'key' => Setting::KEY_PRICING_DIARIO_DAY,
                'name' => 'Preço Diária',
                'description' => 'Valor padrão para diária completa (12h)',
                'value' => '300',
                'default_value' => '300',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 150, 'max' => 800],
                'is_public' => true,
                'display_order' => 4,
            ],
            [
                'key' => Setting::KEY_PRICING_MENSAL_MONTH,
                'name' => 'Preço Mensal',
                'description' => 'Valor padrão para contrato mensal',
                'value' => '6000',
                'default_value' => '6000',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 3000, 'max' => 15000],
                'is_public' => true,
                'display_order' => 5,
            ],
            [
                'key' => Setting::KEY_PRICING_NIGHT_SURCHARGE,
                'name' => 'Adicional Noturno',
                'description' => 'Percentual adicional para horário noturno (22h-6h)',
                'value' => '20',
                'default_value' => '20',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 50],
                'is_public' => true,
                'display_order' => 6,
            ],
            [
                'key' => Setting::KEY_PRICING_WEEKEND_SURCHARGE,
                'name' => 'Adicional Fim de Semana',
                'description' => 'Percentual adicional para sábados e domingos',
                'value' => '30',
                'default_value' => '30',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 100],
                'is_public' => true,
                'display_order' => 7,
            ],
            [
                'key' => Setting::KEY_PRICING_HOLIDAY_SURCHARGE,
                'name' => 'Adicional Feriado',
                'description' => 'Percentual adicional para feriados',
                'value' => '50',
                'default_value' => '50',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 100],
                'is_public' => true,
                'display_order' => 8,
            ],
            [
                'key' => Setting::KEY_PRICING_MONTHLY_DISCOUNT,
                'name' => 'Desconto Pacote Mensal',
                'description' => 'Percentual de desconto para pacotes mensais',
                'value' => '10',
                'default_value' => '10',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 30],
                'is_public' => true,
                'display_order' => 9,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Configurações de Margem.
     */
    protected function seedMarginSettings(): void
    {
        $categoryId = SettingCategory::MARGIN;

        $settings = [
            [
                'key' => Setting::KEY_MARGIN_MINIMUM,
                'name' => 'Margem Mínima',
                'description' => 'Margem mínima aceitável sobre o custo',
                'value' => '25',
                'default_value' => '25',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => '%',
                'validation_rules' => ['min' => 10, 'max' => 50],
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_MARGIN_TARGET,
                'name' => 'Margem Alvo',
                'description' => 'Margem desejada sobre o custo',
                'value' => '30',
                'default_value' => '30',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => '%',
                'validation_rules' => ['min' => 15, 'max' => 60],
                'display_order' => 2,
            ],
            [
                'key' => Setting::KEY_MARGIN_ALERT,
                'name' => 'Alerta de Margem',
                'description' => 'Margem abaixo da qual gera alerta',
                'value' => '20',
                'default_value' => '20',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => '%',
                'validation_rules' => ['min' => 5, 'max' => 40],
                'display_order' => 3,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Configurações de Repasse.
     */
    protected function seedPayoutSettings(): void
    {
        $categoryId = SettingCategory::PAYOUT;

        $settings = [
            [
                'key' => Setting::KEY_PAYOUT_FREQUENCY,
                'name' => 'Frequência de Repasse',
                'description' => 'Frequência dos repasses aos cuidadores',
                'value' => 'weekly',
                'default_value' => 'weekly',
                'value_type' => Setting::TYPE_STRING,
                'unit' => null,
                'validation_rules' => ['in' => ['weekly', 'biweekly', 'monthly']],
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_PAYOUT_DAY,
                'name' => 'Dia do Repasse',
                'description' => 'Dia da semana para processamento (1=Seg, 5=Sex)',
                'value' => '5',
                'default_value' => '5',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => null,
                'validation_rules' => ['min' => 1, 'max' => 7],
                'display_order' => 2,
            ],
            [
                'key' => Setting::KEY_PAYOUT_MINIMUM,
                'name' => 'Valor Mínimo para Repasse',
                'description' => 'Valor mínimo acumulado para processar repasse',
                'value' => '50',
                'default_value' => '50',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 0, 'max' => 500],
                'display_order' => 3,
            ],
            [
                'key' => Setting::KEY_PAYOUT_RELEASE_DAYS,
                'name' => 'Dias para Liberação',
                'description' => 'Dias após serviço para liberar repasse',
                'value' => '3',
                'default_value' => '3',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => 'dias',
                'validation_rules' => ['min' => 0, 'max' => 15],
                'display_order' => 4,
            ],
            [
                'key' => Setting::KEY_PAYOUT_PIX_FEE,
                'name' => 'Taxa PIX',
                'description' => 'Taxa de transferência PIX (se houver)',
                'value' => '0',
                'default_value' => '0',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 0, 'max' => 10],
                'display_order' => 5,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Configurações Fiscais.
     */
    protected function seedFiscalSettings(): void
    {
        $categoryId = SettingCategory::FISCAL;

        $settings = [
            [
                'key' => Setting::KEY_FISCAL_AUTO_ISSUE,
                'name' => 'Emissão Automática de NF',
                'description' => 'Emitir nota fiscal automaticamente após pagamento',
                'value' => '1',
                'default_value' => '1',
                'value_type' => Setting::TYPE_BOOLEAN,
                'unit' => null,
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_FISCAL_ISS_RATE,
                'name' => 'Alíquota ISS',
                'description' => 'Alíquota do ISS para emissão de NFS-e',
                'value' => '5',
                'default_value' => '5',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 2, 'max' => 5],
                'display_order' => 2,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Configurações de Limites.
     */
    protected function seedLimitsSettings(): void
    {
        $categoryId = SettingCategory::LIMITS;

        $settings = [
            [
                'key' => Setting::KEY_LIMIT_CREDIT_PF,
                'name' => 'Crédito Inicial PF',
                'description' => 'Limite de crédito inicial para pessoa física',
                'value' => '0',
                'default_value' => '0',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 0, 'max' => 5000],
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_LIMIT_CREDIT_PJ,
                'name' => 'Crédito Inicial PJ',
                'description' => 'Limite de crédito inicial para pessoa jurídica',
                'value' => '0',
                'default_value' => '0',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 0, 'max' => 20000],
                'display_order' => 2,
            ],
            [
                'key' => Setting::KEY_LIMIT_BLOCK_DAYS,
                'name' => 'Dias para Bloqueio',
                'description' => 'Dias em atraso para bloquear cliente',
                'value' => '7',
                'default_value' => '7',
                'value_type' => Setting::TYPE_INTEGER,
                'unit' => 'dias',
                'validation_rules' => ['min' => 1, 'max' => 30],
                'display_order' => 3,
            ],
            [
                'key' => Setting::KEY_LIMIT_MAX_OVERDUE,
                'name' => 'Máximo em Atraso',
                'description' => 'Valor máximo tolerado em atraso',
                'value' => '500',
                'default_value' => '500',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => 'R$',
                'validation_rules' => ['min' => 0, 'max' => 5000],
                'display_order' => 4,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Configurações de Bônus.
     */
    protected function seedBonusSettings(): void
    {
        $categoryId = SettingCategory::BONUS;

        $settings = [
            [
                'key' => Setting::KEY_BONUS_RATING_MIN,
                'name' => 'Avaliação Mínima para Bônus',
                'description' => 'Nota mínima para receber bônus por avaliação',
                'value' => '4.5',
                'default_value' => '4.5',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => null,
                'validation_rules' => ['min' => 3, 'max' => 5],
                'display_order' => 1,
            ],
            [
                'key' => Setting::KEY_BONUS_RATING_PERCENT,
                'name' => 'Bônus por Avaliação',
                'description' => 'Percentual adicional na comissão por boa avaliação',
                'value' => '2',
                'default_value' => '2',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 10],
                'display_order' => 2,
            ],
            [
                'key' => Setting::KEY_BONUS_TENURE_6M,
                'name' => 'Bônus 6 Meses',
                'description' => 'Bônus adicional para cuidadores com 6+ meses',
                'value' => '1',
                'default_value' => '1',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 5],
                'display_order' => 3,
            ],
            [
                'key' => Setting::KEY_BONUS_TENURE_12M,
                'name' => 'Bônus 12 Meses',
                'description' => 'Bônus adicional para cuidadores com 12+ meses',
                'value' => '2',
                'default_value' => '2',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 5],
                'display_order' => 4,
            ],
            [
                'key' => Setting::KEY_BONUS_TENURE_24M,
                'name' => 'Bônus 24 Meses',
                'description' => 'Bônus adicional para cuidadores com 24+ meses',
                'value' => '3',
                'default_value' => '3',
                'value_type' => Setting::TYPE_DECIMAL,
                'unit' => '%',
                'validation_rules' => ['min' => 0, 'max' => 10],
                'display_order' => 5,
            ],
        ];

        $this->createSettings($categoryId, $settings);
    }

    /**
     * Cria configurações de uma categoria.
     */
    protected function createSettings(int $categoryId, array $settings): void
    {
        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'key' => $setting['key'],
                ],
                array_merge($setting, [
                    'category_id' => $categoryId,
                    'is_editable' => $setting['is_editable'] ?? true,
                    'is_public' => $setting['is_public'] ?? false,
                ])
            );
        }
    }
}
