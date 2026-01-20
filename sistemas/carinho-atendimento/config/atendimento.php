<?php

return [
    'timezone' => env('ATENDIMENTO_TIMEZONE', 'America/Sao_Paulo'),
    'working_hours' => [
        'start' => env('ATENDIMENTO_WORK_START', '08:00'),
        'end' => env('ATENDIMENTO_WORK_END', '18:00'),
    ],
    'funnel' => [
        'stages' => [
            'new' => 'recepcao',
            'triage' => 'entendimento',
            'proposal' => 'proposta',
            'waiting' => 'encaminhamento',
        ],
    ],
    'auto_rules' => [
        'first_response' => 'first_response',
        'after_hours' => 'after_hours',
        'feedback_request' => 'feedback_request',
    ],
];
