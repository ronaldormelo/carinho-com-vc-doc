<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuracoes de Agendamento
    |--------------------------------------------------------------------------
    */
    'scheduling' => [
        // Antecedencia minima para agendamento (em horas)
        'min_advance_hours' => env('SCHEDULE_MIN_ADVANCE', 24),

        // Duracao minima de atendimento (em horas)
        'min_duration_hours' => env('SCHEDULE_MIN_DURATION', 4),

        // Duracao maxima de atendimento (em horas)
        'max_duration_hours' => env('SCHEDULE_MAX_DURATION', 12),

        // Intervalo minimo entre atendimentos do mesmo cuidador (em minutos)
        'min_gap_minutes' => env('SCHEDULE_MIN_GAP', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuracoes de Match
    |--------------------------------------------------------------------------
    */
    'matching' => [
        // Peso para score de habilidades
        'skill_weight' => 0.35,

        // Peso para score de disponibilidade
        'availability_weight' => 0.25,

        // Peso para score de regiao/distancia
        'region_weight' => 0.20,

        // Peso para avaliacao media
        'rating_weight' => 0.20,

        // Score minimo para match automatico
        'min_auto_match_score' => 70,

        // Maximo de candidatos por match
        'max_candidates' => 10,

        // Raio maximo para busca de cuidadores (em km)
        'max_radius_km' => env('MATCH_MAX_RADIUS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuracoes de Check-in/Check-out
    |--------------------------------------------------------------------------
    */
    'checkin' => [
        // Tolerancia para check-in antecipado (em minutos)
        'early_tolerance_minutes' => env('CHECKIN_EARLY_TOLERANCE', 30),

        // Tolerancia para check-in atrasado (em minutos)
        'late_tolerance_minutes' => env('CHECKIN_LATE_TOLERANCE', 15),

        // Requerer localizacao para check-in
        'require_location' => env('CHECKIN_REQUIRE_LOCATION', true),

        // Distancia maxima permitida do endereco (em metros)
        'max_distance_meters' => env('CHECKIN_MAX_DISTANCE', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Politicas de Cancelamento
    |--------------------------------------------------------------------------
    */
    'cancellation' => [
        // Prazo para cancelamento sem custo (em horas antes do servico)
        'free_cancellation_hours' => env('CANCEL_FREE_HOURS', 48),

        // Prazo para cancelamento com taxa reduzida (em horas)
        'reduced_fee_hours' => env('CANCEL_REDUCED_HOURS', 24),

        // Percentual de taxa reduzida
        'reduced_fee_percent' => 30,

        // Percentual de taxa integral (cancelamento tardio)
        'full_fee_percent' => 50,

        // Maximo de cancelamentos por cliente por mes
        'max_monthly_cancellations' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuracoes de Substituicao
    |--------------------------------------------------------------------------
    */
    'substitution' => [
        // Tempo maximo para encontrar substituto (em minutos)
        'max_search_time_minutes' => env('SUBSTITUTION_MAX_TIME', 120),

        // Prioridade para cuidadores da mesma regiao
        'same_region_priority' => true,

        // Notificar cliente automaticamente
        'auto_notify_client' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuracoes de Notificacoes
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        // Enviar lembrete X horas antes
        'reminder_hours_before' => [24, 2],

        // Notificar check-in/out para cliente
        'notify_checkin' => true,
        'notify_checkout' => true,

        // Canais habilitados
        'channels' => ['whatsapp', 'email', 'push'],

        // Canal preferencial
        'preferred_channel' => 'whatsapp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuracoes de Emergencia
    |--------------------------------------------------------------------------
    */
    'emergency' => [
        // Tempo maximo de resposta por severidade (em minutos)
        'response_time' => [
            'low' => 60,
            'medium' => 30,
            'high' => 15,
            'critical' => 5,
        ],

        // Escalonamento automatico apos X minutos sem resposta
        'auto_escalate_minutes' => 10,

        // Email para alertas criticos
        'alert_email' => env('EMERGENCY_ALERT_EMAIL', 'emergencia@carinho.com.vc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA e Metricas
    |--------------------------------------------------------------------------
    */
    'sla' => [
        // Tempo maximo para alocacao de cuidador (em horas)
        'allocation_time_hours' => 4,

        // Taxa minima de ocupacao para alertar
        'min_occupancy_rate' => 0.60,

        // Taxa maxima de cancelamento aceitavel
        'max_cancellation_rate' => 0.10,

        // Avaliacao minima para manter cuidador ativo
        'min_rating' => 3.5,
    ],
];
