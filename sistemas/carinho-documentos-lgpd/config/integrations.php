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
    | AWS S3 Storage
    |--------------------------------------------------------------------------
    |
    | Configuracao para armazenamento de documentos no Amazon S3.
    | Documentacao: https://docs.aws.amazon.com/sdk-for-php/
    |
    | Recursos utilizados:
    | - Criptografia server-side (AES-256)
    | - URLs pre-assinadas com expiracao
    | - Versionamento de objetos
    |
    */
    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'sa-east-1'),
        'bucket' => env('AWS_BUCKET', 'carinho-documentos'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),

        // Configuracoes de upload
        'upload' => [
            'max_size_mb' => env('AWS_UPLOAD_MAX_SIZE_MB', 25),
            'allowed_mimes' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
        ],

        // Criptografia
        'encryption' => env('AWS_ENCRYPTION', 'AES256'),

        // Duracao padrao de URLs assinadas (em minutos)
        'signed_url_expiration' => env('AWS_SIGNED_URL_EXPIRATION', 60),

        // Prefixos de pastas
        'prefixes' => [
            'clients' => 'clients',
            'caregivers' => 'caregivers',
            'contracts' => 'contracts',
            'templates' => 'templates',
            'exports' => 'exports',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (Z-API)
    |--------------------------------------------------------------------------
    |
    | Integracao com Z-API para envio de mensagens WhatsApp.
    | Documentacao: https://developer.z-api.io/
    |
    | Endpoints utilizados:
    | - POST /send-text - Envio de texto
    | - POST /send-document - Envio de documentos
    | - POST /send-link - Envio de links com preview
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
    | CRM (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema CRM.
    | Notifica sobre contratos assinados e consentimentos.
    |
    */
    'crm' => [
        'base_url' => env('CRM_BASE_URL', 'https://crm.carinho.com.vc/api'),
        'token' => env('CRM_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cuidadores (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema de Cuidadores.
    | Gerencia documentos e termos de cuidadores.
    |
    */
    'cuidadores' => [
        'base_url' => env('CUIDADORES_BASE_URL', 'https://cuidadores.carinho.com.vc/api'),
        'token' => env('CUIDADORES_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Financeiro (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema Financeiro.
    | Armazena notas e comprovantes.
    |
    */
    'financeiro' => [
        'base_url' => env('FINANCEIRO_BASE_URL', 'https://financeiro.carinho.com.vc/api'),
        'token' => env('FINANCEIRO_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Atendimento (Interno)
    |--------------------------------------------------------------------------
    |
    | Integracao com o sistema de Atendimento.
    | Envia termos e politica de privacidade.
    |
    */
    'atendimento' => [
        'base_url' => env('ATENDIMENTO_BASE_URL', 'https://atendimento.carinho.com.vc/api'),
        'token' => env('ATENDIMENTO_TOKEN'),
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
    | Email
    |--------------------------------------------------------------------------
    */
    'email' => [
        'from' => env('EMAIL_FROM', 'documentos@carinho.com.vc'),
        'reply_to' => env('EMAIL_REPLY_TO', 'contato@carinho.com.vc'),
    ],
];
