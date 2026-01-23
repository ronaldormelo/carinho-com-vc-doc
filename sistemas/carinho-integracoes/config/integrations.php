<?php

/**
 * Configuracoes de integracoes do sistema Carinho Integracoes.
 *
 * Este arquivo centraliza todas as configuracoes de conexao com
 * sistemas externos e internos do ecossistema Carinho.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (Z-API)
    |--------------------------------------------------------------------------
    |
    | Configuracoes para integracao com Z-API para envio e recebimento
    | de mensagens via WhatsApp.
    |
    | Documentacao: https://developer.z-api.io/
    |
    */

    'whatsapp' => [
        'enabled' => env('ZAPI_ENABLED', false),
        'base_url' => env('ZAPI_BASE_URL', 'https://api.z-api.io'),
        'instance_id' => env('ZAPI_INSTANCE_ID'),
        'token' => env('ZAPI_TOKEN'),
        'client_token' => env('ZAPI_CLIENT_TOKEN'),
        'webhook_secret' => env('ZAPI_WEBHOOK_SECRET'),
        'connect_timeout' => (int) env('ZAPI_CONNECT_TIMEOUT', 3),
        'timeout' => (int) env('ZAPI_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistemas Internos Carinho
    |--------------------------------------------------------------------------
    |
    | Configuracoes de conexao com os demais sistemas do ecossistema.
    | Cada sistema possui sua URL base e API key para autenticacao.
    |
    */

    'site' => [
        'url' => env('CARINHO_SITE_URL', 'https://site.carinho.com.vc'),
        'api_key' => env('CARINHO_SITE_API_KEY'),
        'timeout' => 10,
    ],

    'crm' => [
        'url' => env('CARINHO_CRM_URL', 'https://crm.carinho.com.vc'),
        'api_key' => env('CARINHO_CRM_API_KEY'),
        'timeout' => 10,
    ],

    'atendimento' => [
        'url' => env('CARINHO_ATENDIMENTO_URL', 'https://atendimento.carinho.com.vc'),
        'api_key' => env('CARINHO_ATENDIMENTO_API_KEY'),
        'timeout' => 10,
    ],

    'operacao' => [
        'url' => env('CARINHO_OPERACAO_URL', 'https://operacao.carinho.com.vc'),
        'api_key' => env('CARINHO_OPERACAO_API_KEY'),
        'timeout' => 10,
    ],

    'financeiro' => [
        'url' => env('CARINHO_FINANCEIRO_URL', 'https://financeiro.carinho.com.vc'),
        'api_key' => env('CARINHO_FINANCEIRO_API_KEY'),
        'timeout' => 10,
    ],

    'cuidadores' => [
        'url' => env('CARINHO_CUIDADORES_URL', 'https://cuidadores.carinho.com.vc'),
        'api_key' => env('CARINHO_CUIDADORES_API_KEY'),
        'timeout' => 10,
    ],

    'documentos' => [
        'url' => env('CARINHO_DOCUMENTOS_URL', 'https://documentos.carinho.com.vc'),
        'api_key' => env('CARINHO_DOCUMENTOS_API_KEY'),
        'timeout' => 10,
    ],

    'marketing' => [
        'url' => env('CARINHO_MARKETING_URL', 'https://marketing.carinho.com.vc'),
        'api_key' => env('CARINHO_MARKETING_API_KEY'),
        'timeout' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry e Backoff
    |--------------------------------------------------------------------------
    |
    | Configuracoes para tentativas de reprocessamento em caso de falha.
    |
    */

    'retry' => [
        'max_attempts' => (int) env('RETRY_MAX_ATTEMPTS', 5),
        'base_delay' => (int) env('RETRY_BASE_DELAY', 60), // segundos
        'backoff_multiplier' => (int) env('RETRY_BACKOFF_MULTIPLIER', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuracoes de limite de requisicoes por cliente.
    |
    */

    'rate_limit' => [
        'per_minute' => (int) env('RATE_LIMIT_PER_MINUTE', 60),
        'burst' => (int) env('RATE_LIMIT_BURST', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dead Letter Queue
    |--------------------------------------------------------------------------
    |
    | Configuracoes para eventos que falharam apos todas tentativas.
    |
    */

    'dlq' => [
        'enabled' => env('DLQ_ENABLED', true),
        'max_retries' => (int) env('DLQ_MAX_RETRIES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Observabilidade
    |--------------------------------------------------------------------------
    |
    | Configuracoes de logging e metricas.
    |
    */

    'logging' => [
        'events' => env('LOG_EVENTS', true),
    ],

    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
    ],
];
