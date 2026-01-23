<?php

/**
 * Configuracoes especificas do site institucional.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Tipos de Servico
    |--------------------------------------------------------------------------
    */

    'service_types' => [
        'horista' => [
            'code' => 'horista',
            'label' => 'Horista',
            'description' => 'Atendimento por hora para demandas pontuais, visitas curtas e acompanhamento eventual.',
            'icon' => 'clock',
            'min_hours' => 2,
        ],
        'diario' => [
            'code' => 'diario',
            'label' => 'Diario',
            'description' => 'Turnos diurnos ou noturnos recorrentes por semana.',
            'icon' => 'sun',
            'min_hours' => 6,
        ],
        'mensal' => [
            'code' => 'mensal',
            'label' => 'Mensal',
            'description' => 'Continuidade com escala definida e previsibilidade de custo.',
            'icon' => 'calendar',
            'min_hours' => 120,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Niveis de Urgencia
    |--------------------------------------------------------------------------
    */

    'urgency_levels' => [
        'hoje' => [
            'code' => 'hoje',
            'label' => 'Hoje',
            'description' => 'Preciso de um cuidador para hoje.',
            'priority' => 1,
        ],
        'semana' => [
            'code' => 'semana',
            'label' => 'Esta semana',
            'description' => 'Preciso de um cuidador nos proximos dias.',
            'priority' => 2,
        ],
        'sem_data' => [
            'code' => 'sem_data',
            'label' => 'Sem data definida',
            'description' => 'Ainda estou pesquisando opcoes.',
            'priority' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Politica de Pagamento
    |--------------------------------------------------------------------------
    | O pagamento e sempre ADIANTADO (pre-pago).
    */

    'payment_policy' => [
        'type' => 'adiantado',
        'advance_hours' => 24, // Horas antes do servico
        'description' => 'O pagamento deve ser realizado com antecedencia minima de 24 horas antes do inicio do servico.',
        'methods' => ['pix', 'boleto', 'cartao'],
        'late_interest_daily' => 0.033, // ~1% ao mes
        'late_penalty' => 2.0, // 2% de multa
    ],

    /*
    |--------------------------------------------------------------------------
    | Politica de Cancelamento
    |--------------------------------------------------------------------------
    */

    'cancellation_policy' => [
        'free_hours' => 24, // Cancelamento gratuito se >24h antes
        'partial_hours' => 6, // Reembolso parcial se entre 6h e 24h
        'rules' => [
            [
                'condition' => 'Mais de 24 horas antes do servico',
                'refund' => 100,
                'description' => 'Reembolso total do valor pago.',
            ],
            [
                'condition' => 'Entre 6 e 24 horas antes do servico',
                'refund' => 50,
                'description' => 'Reembolso de 50% do valor pago.',
            ],
            [
                'condition' => 'Menos de 6 horas antes do servico',
                'refund' => 0,
                'description' => 'Sem reembolso. O valor e retido integralmente.',
            ],
        ],
        'admin_fee' => 5.0, // Taxa administrativa em reembolsos parciais
        'caregiver_cancellation' => 'Em caso de cancelamento pelo cuidador, o cliente recebe reembolso total.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Comissoes e Percentuais do Cuidador
    |--------------------------------------------------------------------------
    */

    'caregiver_commission' => [
        'horista' => 70, // 70% para o cuidador
        'diario' => 72, // 72% para o cuidador
        'mensal' => 75, // 75% para o cuidador
        'bonus' => [
            'rating' => 2, // Ate +2% por avaliacao alta
            'tenure' => 3, // Ate +3% por tempo de casa
        ],
        'description' => 'O cuidador recebe entre 70% e 75% do valor do servico, podendo receber bonus por avaliacao e tempo de casa.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Politica de Repasses
    |--------------------------------------------------------------------------
    */

    'payout_policy' => [
        'frequency' => 'semanal',
        'day' => 'friday',
        'min_value' => 50.00, // Valor minimo para repasse
        'release_days' => 3, // Dias apos conclusao do servico
        'description' => 'Os repasses sao realizados semanalmente (sextas-feiras), com valor minimo de R$ 50,00 e liberacao 3 dias apos a conclusao do servico.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Politica de Emergencias
    |--------------------------------------------------------------------------
    */

    'emergency_policy' => [
        'channels' => [
            'whatsapp' => 'Numero exclusivo para emergencias',
            'email' => 'emergencia@carinho.com.vc',
            'phone' => 'Ligacao direta para casos criticos',
        ],
        'response_time' => [
            'critical' => '15 minutos',
            'high' => '30 minutos',
            'medium' => '2 horas',
        ],
        'types' => [
            [
                'type' => 'Emergencia medica',
                'action' => 'Ligar 192 (SAMU) e notificar familiar responsavel.',
                'severity' => 'critical',
            ],
            [
                'type' => 'Ausencia do cuidador',
                'action' => 'Acionamento imediato de cuidador substituto.',
                'severity' => 'high',
            ],
            [
                'type' => 'Atraso do cuidador',
                'action' => 'Contato com cuidador e notificacao ao cliente.',
                'severity' => 'medium',
            ],
            [
                'type' => 'Problema no atendimento',
                'action' => 'Registro de ocorrencia e contato com supervisor.',
                'severity' => 'medium',
            ],
        ],
        'escalation' => [
            'level_1' => 'Atendimento - resposta imediata',
            'level_2' => 'Supervisor - 15 minutos sem resolucao',
            'level_3' => 'Gerente - 30 minutos sem resolucao',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA de Atendimento
    |--------------------------------------------------------------------------
    */

    'sla' => [
        'first_response' => 5, // minutos para primeira resposta
        'resolution' => 30, // minutos para resolucao simples
        'business_hours' => [
            'start' => '08:00',
            'end' => '20:00',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cidades Atendidas
    |--------------------------------------------------------------------------
    */

    'cities' => [
        'sao_paulo' => [
            'name' => 'Sao Paulo',
            'state' => 'SP',
            'active' => true,
            'neighborhoods' => [], // Todos os bairros
        ],
        // Expandir conforme necessidade
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (em segundos)
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'pages' => 3600, // 1 hora
        'settings' => 86400, // 24 horas
        'legal_docs' => 86400, // 24 horas
    ],
];
