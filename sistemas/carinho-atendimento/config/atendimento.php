<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações Gerais
    |--------------------------------------------------------------------------
    */
    'timezone' => env('ATENDIMENTO_TIMEZONE', 'America/Sao_Paulo'),
    
    /*
    |--------------------------------------------------------------------------
    | Horário de Funcionamento
    |--------------------------------------------------------------------------
    | Define o horário de atendimento humano. Fora deste horário,
    | mensagens automáticas de "fora do horário" serão enviadas.
    */
    'working_hours' => [
        'start' => env('ATENDIMENTO_WORK_START', '08:00'),
        'end' => env('ATENDIMENTO_WORK_END', '18:00'),
        'saturday_start' => env('ATENDIMENTO_SATURDAY_START', '08:00'),
        'saturday_end' => env('ATENDIMENTO_SATURDAY_END', '12:00'),
        'work_on_saturday' => env('ATENDIMENTO_WORK_SATURDAY', true),
        'work_on_sunday' => env('ATENDIMENTO_WORK_SUNDAY', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Funil de Atendimento
    |--------------------------------------------------------------------------
    | Mapeia os códigos do funil para labels amigáveis.
    */
    'funnel' => [
        'stages' => [
            'new' => 'Novo',
            'triage' => 'Em Triagem',
            'proposal' => 'Proposta Enviada',
            'waiting' => 'Aguardando Resposta',
            'active' => 'Ativo',
            'lost' => 'Perdido',
            'closed' => 'Encerrado',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Regras de Automação
    |--------------------------------------------------------------------------
    | Mapeia triggers para chaves de templates de mensagem.
    */
    'auto_rules' => [
        'first_response' => 'first_response',
        'after_hours' => 'after_hours',
        'feedback_request' => 'feedback_request',
        'proposal_sent' => 'proposal_sent',
        'waiting_response_reminder' => 'waiting_response_reminder',
        'service_confirmation' => 'service_confirmation',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Níveis de Suporte
    |--------------------------------------------------------------------------
    | Configurações dos níveis de suporte (N1, N2, N3).
    */
    'support_levels' => [
        'n1' => [
            'label' => 'Nível 1 - Atendimento',
            'description' => 'Primeiro contato, triagem e informações básicas',
            'can_escalate_to' => ['n2'],
        ],
        'n2' => [
            'label' => 'Nível 2 - Suporte',
            'description' => 'Questões técnicas, propostas e negociação',
            'can_escalate_to' => ['n3'],
        ],
        'n3' => [
            'label' => 'Nível 3 - Especialista',
            'description' => 'Casos complexos, reclamações críticas e emergências',
            'can_escalate_to' => [],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | SLA Padrão (em minutos)
    |--------------------------------------------------------------------------
    | Tempos máximos de resposta e resolução por prioridade.
    | Configuração detalhada está na tabela sla_configurations.
    */
    'sla' => [
        'default_warning_threshold_percent' => 80,
        'check_interval_minutes' => 5,
        'auto_escalate_on_breach' => env('ATENDIMENTO_AUTO_ESCALATE', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Agentes
    |--------------------------------------------------------------------------
    | Configurações padrão para agentes.
    */
    'agents' => [
        'default_max_concurrent_conversations' => 5,
        'auto_assign_enabled' => env('ATENDIMENTO_AUTO_ASSIGN', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Triagem
    |--------------------------------------------------------------------------
    | Configurações do checklist de triagem.
    */
    'triage' => [
        'require_complete_before_proposal' => true,
        'auto_calculate_priority' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Notificações
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'sla_alert_channels' => ['database', 'slack'],
        'escalation_channels' => ['database', 'slack'],
        'incident_channels' => ['database', 'slack', 'email'],
    ],
];
