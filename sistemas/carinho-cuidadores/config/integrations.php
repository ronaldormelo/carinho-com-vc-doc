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
    | Integracao com o sistema CRM para sincronizacao de dados.
    |
    */
    'crm' => [
        'base_url' => env('CRM_BASE_URL', 'https://crm.carinho.com.vc/api'),
        'token' => env('CRM_TOKEN'),
        'timeout' => env('CRM_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Operacao (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema de Operacao para alocacao e servicos.
    |
    */
    'operacao' => [
        'base_url' => env('OPERACAO_BASE_URL', 'https://operacao.carinho.com.vc/api'),
        'token' => env('OPERACAO_TOKEN'),
        'timeout' => env('OPERACAO_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentos/LGPD (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema de Documentos para armazenamento seguro
    | e assinatura digital.
    |
    */
    'documentos' => [
        'base_url' => env('DOCUMENTOS_BASE_URL', 'https://documentos.carinho.com.vc/api'),
        'token' => env('DOCUMENTOS_TOKEN'),
        'timeout' => env('DOCUMENTOS_TIMEOUT', 15),
        'upload_timeout' => env('DOCUMENTOS_UPLOAD_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Atendimento (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema de Atendimento para comunicados.
    |
    */
    'atendimento' => [
        'base_url' => env('ATENDIMENTO_BASE_URL', 'https://atendimento.carinho.com.vc/api'),
        'token' => env('ATENDIMENTO_TOKEN'),
        'timeout' => env('ATENDIMENTO_TIMEOUT', 8),
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
        'from' => env('EMAIL_FROM', 'cuidadores@carinho.com.vc'),
        'reply_to' => env('EMAIL_REPLY_TO', 'contato@carinho.com.vc'),
    ],
];
