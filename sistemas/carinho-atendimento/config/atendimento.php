<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fuso Horario
    |--------------------------------------------------------------------------
    | Fuso horario usado para calculo de horario comercial e SLA.
    */
    'timezone' => env('ATENDIMENTO_TIMEZONE', 'America/Sao_Paulo'),

    /*
    |--------------------------------------------------------------------------
    | Horario Comercial
    |--------------------------------------------------------------------------
    | Configuracao de horarios de atendimento.
    | saturday: define se atende aos sabados
    | saturday_end: horario de encerramento aos sabados
    */
    'working_hours' => [
        'start' => env('ATENDIMENTO_WORK_START', '08:00'),
        'end' => env('ATENDIMENTO_WORK_END', '18:00'),
        'saturday' => env('ATENDIMENTO_WORK_SATURDAY', false),
        'saturday_end' => env('ATENDIMENTO_SATURDAY_END', '12:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Funil de Atendimento
    |--------------------------------------------------------------------------
    | Mapeamento de status para etapas do funil comercial.
    */
    'funnel' => [
        'stages' => [
            'new' => 'recepcao',
            'triage' => 'entendimento',
            'proposal' => 'proposta',
            'waiting' => 'encaminhamento',
            'active' => 'ativo',
            'lost' => 'perdido',
            'closed' => 'encerrado',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Niveis de Suporte
    |--------------------------------------------------------------------------
    | Configuracao de escalonamento entre niveis.
    */
    'support_levels' => [
        'n1' => [
            'name' => 'Atendimento',
            'escalation_minutes' => env('ATENDIMENTO_N1_ESCALATION', 15),
        ],
        'n2' => [
            'name' => 'Supervisao',
            'escalation_minutes' => env('ATENDIMENTO_N2_ESCALATION', 30),
        ],
        'n3' => [
            'name' => 'Gestao',
            'escalation_minutes' => env('ATENDIMENTO_N3_ESCALATION', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Regras de Automacao
    |--------------------------------------------------------------------------
    | Mapeamento de gatilhos para templates de mensagens automaticas.
    */
    'auto_rules' => [
        'first_response' => 'first_response',
        'after_hours' => 'after_hours',
        'feedback_request' => 'feedback_request',
        'follow_up' => 'follow_up',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pesquisa de Satisfacao
    |--------------------------------------------------------------------------
    | Configuracoes da pesquisa de NPS apos atendimento.
    */
    'satisfaction' => [
        'enabled' => env('ATENDIMENTO_SATISFACTION_ENABLED', true),
        'delay_hours' => env('ATENDIMENTO_SATISFACTION_DELAY', 24),
        'scale' => [1, 2, 3, 4, 5],
        'promoter_threshold' => 4,
        'detractor_threshold' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alertas de SLA
    |--------------------------------------------------------------------------
    | Configuracoes de alertas para violacoes de SLA.
    */
    'sla_alerts' => [
        'enabled' => env('ATENDIMENTO_SLA_ALERTS', true),
        'warning_threshold' => 0.8, // Alerta quando 80% do tempo passou
    ],
];
