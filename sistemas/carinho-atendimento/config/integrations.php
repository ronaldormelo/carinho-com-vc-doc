<?php

return [
    'internal' => [
        'token' => env('INTERNAL_API_TOKEN'),
        'timeout' => 5,
    ],
    'whatsapp' => [
        'provider' => 'z-api',
        'base_url' => env('ZAPI_BASE_URL', 'https://api.z-api.io'),
        'instance_id' => env('ZAPI_INSTANCE_ID'),
        'token' => env('ZAPI_TOKEN'),
        'client_token' => env('ZAPI_CLIENT_TOKEN'),
        'webhook_secret' => env('ZAPI_WEBHOOK_SECRET'),
        'timeout' => 10,
        'connect_timeout' => 3,
    ],
    'crm' => [
        'base_url' => env('CRM_BASE_URL', 'https://crm.carinho.com.vc/api'),
        'token' => env('CRM_TOKEN'),
        'timeout' => 8,
    ],
    'operacao' => [
        'base_url' => env('OPERACAO_BASE_URL', 'https://operacao.carinho.com.vc/api'),
        'token' => env('OPERACAO_TOKEN'),
        'timeout' => 8,
    ],
    'integracoes' => [
        'base_url' => env('INTEGRACOES_BASE_URL', 'https://integracoes.carinho.com.vc/api'),
        'token' => env('INTEGRACOES_TOKEN'),
        'timeout' => 8,
    ],
    'email' => [
        'from' => env('EMAIL_FROM', 'propostas@carinho.com.vc'),
        'reply_to' => env('EMAIL_REPLY_TO', 'contato@carinho.com.vc'),
    ],
];
