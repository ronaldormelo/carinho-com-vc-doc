<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema Financeiro
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Políticas de Pagamento
    |--------------------------------------------------------------------------
    |
    | Define regras de pagamento, prazos e políticas do sistema.
    | IMPORTANTE: O pagamento é sempre ADIANTADO - cliente paga antes do serviço.
    |
    */
    'payment' => [
        // Tipo de cobrança: sempre adiantado
        'type' => 'prepaid',

        // Prazo para pagamento antes do início do serviço (em horas)
        'advance_hours' => env('PAYMENT_ADVANCE_HOURS', 24),

        // Dias de tolerância para pagamento após vencimento
        'grace_period_days' => env('PAYMENT_GRACE_DAYS', 0),

        // Juros por dia de atraso (%)
        'late_fee_daily' => env('PAYMENT_LATE_FEE_DAILY', 0.033), // ~1% ao mês

        // Multa por atraso (%)
        'late_penalty' => env('PAYMENT_LATE_PENALTY', 2.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Políticas de Cancelamento
    |--------------------------------------------------------------------------
    |
    | Define regras de cancelamento e reembolso.
    |
    */
    'cancellation' => [
        // Cancelamento sem custo até X horas antes do serviço
        'free_cancellation_hours' => env('CANCELLATION_FREE_HOURS', 24),

        // Reembolso parcial entre X e Y horas antes (% do valor)
        'partial_refund' => [
            'hours_before' => env('CANCELLATION_PARTIAL_HOURS', 12),
            'refund_percent' => env('CANCELLATION_PARTIAL_PERCENT', 50),
        ],

        // Sem reembolso se cancelar com menos de X horas
        'no_refund_hours' => env('CANCELLATION_NO_REFUND_HOURS', 6),

        // Taxa administrativa para cancelamentos (%)
        'admin_fee_percent' => env('CANCELLATION_ADMIN_FEE', 5),

        // Cancelamento por parte do cuidador: cliente recebe reembolso total
        'caregiver_cancel_full_refund' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Comissões e Percentuais
    |--------------------------------------------------------------------------
    |
    | Define a divisão de valores entre empresa e cuidador.
    |
    */
    'commission' => [
        // Percentual que fica com o cuidador (padrão)
        'caregiver_percent' => env('CAREGIVER_COMMISSION_PERCENT', 70),

        // Percentual da empresa (complemento)
        'company_percent' => env('COMPANY_COMMISSION_PERCENT', 30),

        // Comissões diferenciadas por tipo de serviço
        'by_service_type' => [
            'horista' => [
                'caregiver_percent' => env('COMMISSION_HORISTA_CAREGIVER', 70),
            ],
            'diario' => [
                'caregiver_percent' => env('COMMISSION_DIARIO_CAREGIVER', 72),
            ],
            'mensal' => [
                'caregiver_percent' => env('COMMISSION_MENSAL_CAREGIVER', 75),
            ],
        ],

        // Bonus por avaliação (acréscimo no percentual do cuidador)
        'rating_bonus' => [
            'min_rating' => 4.5,
            'bonus_percent' => 2.0,
        ],

        // Bonus por tempo de casa (acréscimo no percentual)
        'tenure_bonus' => [
            '6_months' => 1.0,
            '12_months' => 2.0,
            '24_months' => 3.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Precificação Base
    |--------------------------------------------------------------------------
    |
    | Valores base para cada tipo de serviço. Podem ser ajustados por região.
    |
    */
    'pricing' => [
        // Preço mínimo viável por hora
        'minimum_hourly' => env('PRICING_MIN_HOURLY', 35.00),

        // Preços base por tipo de serviço
        'base' => [
            'horista' => [
                'price_per_hour' => env('PRICING_HORISTA_HOUR', 50.00),
                'minimum_hours' => env('PRICING_HORISTA_MIN_HOURS', 4),
            ],
            'diario' => [
                'price_per_day' => env('PRICING_DIARIO_DAY', 300.00),
                'hours_per_day' => 12,
            ],
            'mensal' => [
                'price_per_month' => env('PRICING_MENSAL_MONTH', 6000.00),
                'days_per_week' => 5,
                'hours_per_day' => 8,
            ],
        ],

        // Adicional noturno (22h às 6h) - percentual
        'night_surcharge' => env('PRICING_NIGHT_SURCHARGE', 20),

        // Adicional final de semana - percentual
        'weekend_surcharge' => env('PRICING_WEEKEND_SURCHARGE', 30),

        // Adicional feriado - percentual
        'holiday_surcharge' => env('PRICING_HOLIDAY_SURCHARGE', 50),

        // Desconto para pacotes mensais (%)
        'monthly_discount' => env('PRICING_MONTHLY_DISCOUNT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Margem e Viabilidade
    |--------------------------------------------------------------------------
    */
    'margin' => [
        // Margem mínima desejada (%)
        'minimum' => env('MARGIN_MINIMUM', 25),

        // Margem alvo (%)
        'target' => env('MARGIN_TARGET', 30),

        // Alerta quando margem ficar abaixo de (%)
        'alert_threshold' => env('MARGIN_ALERT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Repasse aos Cuidadores
    |--------------------------------------------------------------------------
    */
    'payout' => [
        // Frequência de repasse: weekly, biweekly, monthly
        'frequency' => env('PAYOUT_FREQUENCY', 'weekly'),

        // Dia do repasse (1=Segunda, 5=Sexta, etc)
        'day_of_week' => env('PAYOUT_DAY', 5), // Sexta-feira

        // Valor mínimo para repasse
        'minimum_amount' => env('PAYOUT_MINIMUM', 50.00),

        // Dias após conclusão do serviço para liberar repasse
        'release_days' => env('PAYOUT_RELEASE_DAYS', 3),

        // Taxa de transferência PIX (se houver)
        'pix_fee' => env('PAYOUT_PIX_FEE', 0.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notas Fiscais
    |--------------------------------------------------------------------------
    */
    'fiscal' => [
        // Emitir NF automaticamente
        'auto_issue' => env('FISCAL_AUTO_ISSUE', true),

        // CNPJ da empresa
        'cnpj' => env('COMPANY_CNPJ'),

        // Razão social
        'company_name' => env('COMPANY_NAME', 'Carinho com Você Ltda'),

        // Inscrição municipal
        'municipal_registration' => env('COMPANY_IM'),

        // Código de serviço (CNAE)
        'service_code' => env('FISCAL_SERVICE_CODE', '8711500'),

        // Alíquota ISS (%)
        'iss_rate' => env('FISCAL_ISS_RATE', 5.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites e Alertas
    |--------------------------------------------------------------------------
    */
    'limits' => [
        // Limite de crédito inicial para clientes PF
        'initial_credit_pf' => env('LIMIT_CREDIT_PF', 0),

        // Limite de crédito inicial para clientes PJ
        'initial_credit_pj' => env('LIMIT_CREDIT_PJ', 0),

        // Dias de inadimplência para bloquear cliente
        'block_after_days' => env('LIMIT_BLOCK_DAYS', 7),

        // Valor máximo de inadimplência tolerado
        'max_overdue_amount' => env('LIMIT_MAX_OVERDUE', 500.00),
    ],
];
