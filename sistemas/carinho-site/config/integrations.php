<?php

/**
 * Configuracoes de integracoes do sistema Carinho Site.
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
    | Configuracoes para integracao com Z-API para envio de mensagens
    | via WhatsApp e CTA.
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
    | Google Analytics / Tag Manager
    |--------------------------------------------------------------------------
    |
    | Configuracoes para tracking e analytics.
    |
    */

    'analytics' => [
        'enabled' => env('ANALYTICS_ENABLED', true),
        'ga4_id' => env('GA4_MEASUREMENT_ID'),
        'gtm_id' => env('GTM_CONTAINER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Meu Negocio
    |--------------------------------------------------------------------------
    */

    'google_business' => [
        'enabled' => env('GMB_ENABLED', true),
        'place_id' => env('GMB_PLACE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA
    |--------------------------------------------------------------------------
    |
    | Protecao anti-spam para formularios.
    |
    */

    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', true),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
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

    'marketing' => [
        'url' => env('CARINHO_MARKETING_URL', 'https://marketing.carinho.com.vc'),
        'api_key' => env('CARINHO_MARKETING_API_KEY'),
        'timeout' => 10,
    ],

    'integracoes' => [
        'url' => env('CARINHO_INTEGRACOES_URL', 'https://integracoes.carinho.com.vc'),
        'api_key' => env('CARINHO_INTEGRACOES_API_KEY'),
        'timeout' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuracoes de limite de requisicoes.
    |
    */

    'rate_limit' => [
        'forms_per_minute' => (int) env('RATE_LIMIT_FORMS', 5),
        'api_per_minute' => (int) env('RATE_LIMIT_API', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Internal API Token
    |--------------------------------------------------------------------------
    |
    | Token para autenticacao de webhooks e chamadas internas.
    |
    */

    'internal_token' => env('INTERNAL_API_TOKEN'),
];
