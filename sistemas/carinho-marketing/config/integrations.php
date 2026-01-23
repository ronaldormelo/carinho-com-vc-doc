<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token Interno para Comunicacao entre Sistemas
    |--------------------------------------------------------------------------
    */

    'internal' => [
        'token' => env('INTERNAL_API_TOKEN'),
        'timeout' => 5,
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
        'timeout' => 10,
        'connect_timeout' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta (Facebook/Instagram) Marketing API
    |--------------------------------------------------------------------------
    |
    | Integracao com Meta Marketing API para gestao de campanhas.
    | Documentacao: https://developers.facebook.com/docs/marketing-api/
    |
    | Escopos necessarios:
    | - ads_management
    | - ads_read
    | - pages_manage_posts
    | - pages_read_engagement
    | - instagram_basic
    | - instagram_content_publish
    |
    */

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'ad_account_id' => env('META_AD_ACCOUNT_ID'),
        'page_id' => env('META_PAGE_ID'),
        'instagram_account_id' => env('META_INSTAGRAM_ACCOUNT_ID'),
        'pixel_id' => env('META_PIXEL_ID'),
        'api_version' => env('META_API_VERSION', 'v18.0'),
        'base_url' => 'https://graph.facebook.com',
        'timeout' => 30,
        'connect_timeout' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Instagram Graph API
    |--------------------------------------------------------------------------
    |
    | Configuracoes especificas do Instagram (usa token Meta).
    | Documentacao: https://developers.facebook.com/docs/instagram-api/
    |
    */

    'instagram' => [
        'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),
        'enable_insights' => env('INSTAGRAM_ENABLE_INSIGHTS', true),
        'enable_content_publishing' => env('INSTAGRAM_ENABLE_PUBLISHING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Ads API
    |--------------------------------------------------------------------------
    |
    | Integracao com Google Ads API para gestao de campanhas.
    | Documentacao: https://developers.google.com/google-ads/api/docs/start
    |
    */

    'google_ads' => [
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
        'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
        'login_customer_id' => env('GOOGLE_ADS_LOGIN_CUSTOMER_ID'),
        'api_version' => env('GOOGLE_ADS_API_VERSION', 'v15'),
        'timeout' => 30,
        'connect_timeout' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Analytics / Tag Manager
    |--------------------------------------------------------------------------
    |
    | Configuracoes de mensuração e tracking.
    | Documentacao: https://developers.google.com/analytics/devguides/reporting/data/v1
    |
    */

    'google_analytics' => [
        'measurement_id' => env('GA_MEASUREMENT_ID'),
        'api_secret' => env('GA_API_SECRET'),
        'property_id' => env('GA_PROPERTY_ID'),
        'service_account_json' => env('GA_SERVICE_ACCOUNT_JSON'),
    ],

    'google_tag_manager' => [
        'container_id' => env('GTM_CONTAINER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CRM (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema CRM para envio de leads.
    |
    */

    'crm' => [
        'base_url' => env('CRM_BASE_URL', 'https://crm.carinho.com.vc/api'),
        'token' => env('CRM_TOKEN'),
        'timeout' => 8,
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
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Site (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o site principal.
    |
    */

    'site' => [
        'base_url' => env('SITE_BASE_URL', 'https://carinho.com.vc/api'),
        'token' => env('SITE_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Atendimento (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema de Atendimento.
    |
    */

    'atendimento' => [
        'base_url' => env('ATENDIMENTO_BASE_URL', 'https://atendimento.carinho.com.vc/api'),
        'token' => env('ATENDIMENTO_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Builder - UTM
    |--------------------------------------------------------------------------
    */

    'utm' => [
        'default_source' => 'carinho',
        'default_medium' => 'organic',
        'base_url' => env('UTM_BASE_URL', 'https://carinho.com.vc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversion Tracking
    |--------------------------------------------------------------------------
    */

    'conversion' => [
        'facebook_pixel_events' => ['Lead', 'Contact', 'CompleteRegistration'],
        'google_ads_events' => ['conversion', 'lead', 'contact'],
    ],
];
