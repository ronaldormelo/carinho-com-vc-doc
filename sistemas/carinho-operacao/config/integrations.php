<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token Interno para Comunicacao entre Sistemas
    |--------------------------------------------------------------------------
    */
    'internal' => [
        'token' => env('INTERNAL_API_TOKEN'),
        'timeout' => env('INTERNAL_API_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (Z-API)
    |--------------------------------------------------------------------------
    |
    | Integracao com Z-API para envio de mensagens WhatsApp.
    | Documentacao: https://developer.z-api.io/
    |
    */
    'whatsapp' => [
        'provider' => 'z-api',
        'base_url' => env('ZAPI_BASE_URL', 'https://api.z-api.io'),
        'instance_id' => env('ZAPI_INSTANCE_ID'),
        'token' => env('ZAPI_TOKEN'),
        'client_token' => env('ZAPI_CLIENT_TOKEN'),
        'webhook_secret' => env('ZAPI_WEBHOOK_SECRET'),
        'timeout' => env('ZAPI_TIMEOUT', 10),
        'connect_timeout' => env('ZAPI_CONNECT_TIMEOUT', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | CRM (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema CRM para dados de clientes e contratos.
    |
    */
    'crm' => [
        'base_url' => env('CRM_BASE_URL', 'https://crm.carinho.com.vc/api'),
        'token' => env('CRM_TOKEN'),
        'timeout' => env('CRM_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cuidadores (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema de Cuidadores para disponibilidade e perfil.
    |
    */
    'cuidadores' => [
        'base_url' => env('CUIDADORES_BASE_URL', 'https://cuidadores.carinho.com.vc/api'),
        'token' => env('CUIDADORES_TOKEN'),
        'timeout' => env('CUIDADORES_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Atendimento (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema de Atendimento para detalhes da demanda.
    |
    */
    'atendimento' => [
        'base_url' => env('ATENDIMENTO_BASE_URL', 'https://atendimento.carinho.com.vc/api'),
        'token' => env('ATENDIMENTO_TOKEN'),
        'timeout' => env('ATENDIMENTO_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Financeiro (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema Financeiro para cobranca e repasse.
    |
    */
    'financeiro' => [
        'base_url' => env('FINANCEIRO_BASE_URL', 'https://financeiro.carinho.com.vc/api'),
        'token' => env('FINANCEIRO_TOKEN'),
        'timeout' => env('FINANCEIRO_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integracoes Hub (Interno)
    |--------------------------------------------------------------------------
    |
    | Hub central de integracoes para eventos e automacoes.
    |
    */
    'integracoes' => [
        'base_url' => env('INTEGRACOES_BASE_URL', 'https://integracoes.carinho.com.vc/api'),
        'token' => env('INTEGRACOES_TOKEN'),
        'timeout' => env('INTEGRACOES_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    */
    'email' => [
        'from' => env('EMAIL_FROM', 'operacao@carinho.com.vc'),
        'reply_to' => env('EMAIL_REPLY_TO', 'contato@carinho.com.vc'),
    ],
];
