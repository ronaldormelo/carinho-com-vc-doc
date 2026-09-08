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
            'label' => 'Diário',
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
            'description' => 'Preciso de um cuidador nos próximos dias.',
            'priority' => 2,
        ],
        'sem_data' => [
            'code' => 'sem_data',
            'label' => 'Sem data definida',
            'description' => 'Ainda estou pesquisando opções.',
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
        'description' => 'O pagamento deve ser realizado com antecedência mínima de 24 horas antes do início do serviço.',
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
                'condition' => 'Mais de 24 horas antes do serviço',
                'refund' => 100,
                'description' => 'Reembolso total do valor pago.',
            ],
            [
                'condition' => 'Entre 6 e 24 horas antes do serviço',
                'refund' => 50,
                'description' => 'Reembolso de 50% do valor pago.',
            ],
            [
                'condition' => 'Menos de 6 horas antes do serviço',
                'refund' => 0,
                'description' => 'Sem reembolso. O valor é retido integralmente.',
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
        'description' => 'O cuidador recebe entre 70% e 75% do valor do serviço, podendo receber bônus por avaliação e tempo de casa.',
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
        'description' => 'Os repasses são realizados semanalmente (sextas-feiras), com valor mínimo de R$ 50,00 e liberação 3 dias após a conclusão do serviço.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Politica de Emergencias
    |--------------------------------------------------------------------------
    */

    'emergency_policy' => [
        'channels' => [
            'whatsapp' => 'Número exclusivo para emergências',
            'email' => 'emergencia@carinho.com.vc',
            'phone' => 'Ligação direta para casos críticos',
        ],
        'response_time' => [
            'critical' => '15 minutos',
            'high' => '30 minutos',
            'medium' => '2 horas',
        ],
        'types' => [
            [
                'type' => 'Emergência médica',
                'action' => 'Ligar 192 (SAMU) e notificar familiar responsável.',
                'severity' => 'critical',
            ],
            [
                'type' => 'Ausência do cuidador',
                'action' => 'Acionamento imediato de cuidador substituto.',
                'severity' => 'high',
            ],
            [
                'type' => 'Atraso do cuidador',
                'action' => 'Contato com cuidador e notificação ao cliente.',
                'severity' => 'medium',
            ],
            [
                'type' => 'Problema no atendimento',
                'action' => 'Registro de ocorrência e contato com supervisor.',
                'severity' => 'medium',
            ],
        ],
        'escalation' => [
            'level_1' => 'Atendimento - resposta imediata',
            'level_2' => 'Supervisor - 15 minutos sem resolução',
            'level_3' => 'Gerente - 30 minutos sem resolução',
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
            'name' => 'São Paulo',
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
