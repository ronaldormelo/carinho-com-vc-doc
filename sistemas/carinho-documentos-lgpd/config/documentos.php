<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuracoes de Documentos
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Upload
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'max_size_mb' => env('UPLOAD_MAX_SIZE_MB', 25),
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        'allowed_mimes' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URLs Assinadas
    |--------------------------------------------------------------------------
    */
    'signed_urls' => [
        // Duracao padrao em minutos
        'default_expiration' => env('SIGNED_URL_EXPIRATION', 60),

        // Duracao para download de contratos
        'contract_expiration' => env('CONTRACT_URL_EXPIRATION', 1440), // 24 horas

        // Duracao para link de assinatura
        'signature_expiration' => env('SIGNATURE_URL_EXPIRATION', 4320), // 72 horas
    ],

    /*
    |--------------------------------------------------------------------------
    | Assinatura Digital
    |--------------------------------------------------------------------------
    */
    'signature' => [
        // Metodos disponiveis
        'methods' => ['otp', 'click', 'certificate'],

        // Metodo padrao
        'default_method' => env('SIGNATURE_DEFAULT_METHOD', 'otp'),

        // Configuracoes de OTP
        'otp' => [
            'length' => env('OTP_LENGTH', 6),
            'expiration_minutes' => env('OTP_EXPIRATION_MINUTES', 10),
            'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retencao e LGPD
    |--------------------------------------------------------------------------
    */
    'retention' => [
        // Retencao padrao em dias por tipo de documento
        'default_days' => [
            'contrato_cliente' => 3650, // 10 anos
            'contrato_cuidador' => 3650, // 10 anos
            'termos' => 1825, // 5 anos
            'privacidade' => 1825, // 5 anos
        ],

        // Prazo para processar solicitacoes LGPD (em dias)
        'request_deadline_days' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditoria
    |--------------------------------------------------------------------------
    */
    'audit' => [
        // Acoes que sao registradas
        'tracked_actions' => ['view', 'download', 'sign', 'delete'],

        // Retencao de logs em dias
        'log_retention_days' => 1825, // 5 anos
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificacoes
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        // Canais disponiveis
        'channels' => ['email', 'whatsapp'],

        // Canal padrao
        'default_channel' => env('NOTIFICATION_DEFAULT_CHANNEL', 'email'),

        // Eventos que disparam notificacoes
        'events' => [
            'contract_created' => true,
            'contract_signed' => true,
            'document_uploaded' => true,
            'consent_recorded' => true,
            'data_export_ready' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Tempo de cache para templates (em minutos)
        'templates_ttl' => 60,

        // Tempo de cache para metadados (em minutos)
        'metadata_ttl' => 30,

        // Prefixo das chaves de cache
        'prefix' => 'documentos:',
    ],
];
