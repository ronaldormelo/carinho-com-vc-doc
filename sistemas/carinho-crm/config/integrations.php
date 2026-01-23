<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Integrações Externas
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Z-API - WhatsApp Integration
    | Documentação: https://developer.z-api.io/
    |--------------------------------------------------------------------------
    */
    'zapi' => [
        'enabled' => env('ZAPI_ENABLED', false),
        'base_url' => env('ZAPI_BASE_URL', 'https://api.z-api.io'),
        'instance_id' => env('ZAPI_INSTANCE_ID'),
        'token' => env('ZAPI_TOKEN'),
        'client_token' => env('ZAPI_CLIENT_TOKEN'),
        'webhook_url' => env('ZAPI_WEBHOOK_URL'),
        'timeout' => env('ZAPI_TIMEOUT', 30),
        'retry_attempts' => env('ZAPI_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('ZAPI_RETRY_DELAY', 1000), // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrações Internas - Sistemas Carinho
    |--------------------------------------------------------------------------
    */
    'internal' => [
        // Carinho Site
        'site' => [
            'enabled' => env('CARINHO_SITE_ENABLED', true),
            'base_url' => env('CARINHO_SITE_URL', 'https://site.carinho.com.vc'),
            'api_key' => env('CARINHO_SITE_API_KEY'),
            'timeout' => 10,
        ],

        // Carinho Marketing
        'marketing' => [
            'enabled' => env('CARINHO_MARKETING_ENABLED', true),
            'base_url' => env('CARINHO_MARKETING_URL', 'https://marketing.carinho.com.vc'),
            'api_key' => env('CARINHO_MARKETING_API_KEY'),
            'timeout' => 10,
        ],

        // Carinho Atendimento
        'atendimento' => [
            'enabled' => env('CARINHO_ATENDIMENTO_ENABLED', true),
            'base_url' => env('CARINHO_ATENDIMENTO_URL', 'https://atendimento.carinho.com.vc'),
            'api_key' => env('CARINHO_ATENDIMENTO_API_KEY'),
            'timeout' => 10,
        ],

        // Carinho Operação
        'operacao' => [
            'enabled' => env('CARINHO_OPERACAO_ENABLED', true),
            'base_url' => env('CARINHO_OPERACAO_URL', 'https://operacao.carinho.com.vc'),
            'api_key' => env('CARINHO_OPERACAO_API_KEY'),
            'timeout' => 10,
        ],

        // Carinho Financeiro
        'financeiro' => [
            'enabled' => env('CARINHO_FINANCEIRO_ENABLED', true),
            'base_url' => env('CARINHO_FINANCEIRO_URL', 'https://financeiro.carinho.com.vc'),
            'api_key' => env('CARINHO_FINANCEIRO_API_KEY'),
            'timeout' => 10,
        ],

        // Carinho Documentos/LGPD
        'documentos' => [
            'enabled' => env('CARINHO_DOCUMENTOS_ENABLED', true),
            'base_url' => env('CARINHO_DOCUMENTOS_URL', 'https://documentos.carinho.com.vc'),
            'api_key' => env('CARINHO_DOCUMENTOS_API_KEY'),
            'timeout' => 15,
        ],

        // Carinho Cuidadores
        'cuidadores' => [
            'enabled' => env('CARINHO_CUIDADORES_ENABLED', true),
            'base_url' => env('CARINHO_CUIDADORES_URL', 'https://cuidadores.carinho.com.vc'),
            'api_key' => env('CARINHO_CUIDADORES_API_KEY'),
            'timeout' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks Configuration
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'secret' => env('WEBHOOK_SECRET'),
        'tolerance' => 300, // segundos
    ],
];
