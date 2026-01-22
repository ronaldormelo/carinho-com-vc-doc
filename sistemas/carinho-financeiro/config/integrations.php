<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token Interno para Comunicação entre Sistemas
    |--------------------------------------------------------------------------
    */
    'internal' => [
        'token' => env('INTERNAL_API_TOKEN'),
        'timeout' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe - Gateway de Pagamento
    |--------------------------------------------------------------------------
    |
    | Integração com Stripe para processamento de pagamentos.
    | Documentação: https://stripe.com/docs/api
    |
    | Fluxo:
    | 1. Cliente escolhe forma de pagamento (PIX, cartão, boleto)
    | 2. Criamos PaymentIntent no Stripe
    | 3. Stripe processa e envia webhook com resultado
    | 4. Atualizamos status do pagamento
    |
    */
    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', true),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        
        // Configurações de pagamento
        'currency' => env('STRIPE_CURRENCY', 'brl'),
        'payment_methods' => ['pix', 'card', 'boleto'],
        
        // PIX específico
        'pix' => [
            'expires_after' => env('STRIPE_PIX_EXPIRES_MINUTES', 60), // minutos
        ],
        
        // Boleto específico
        'boleto' => [
            'expires_after' => env('STRIPE_BOLETO_EXPIRES_DAYS', 3), // dias
        ],
        
        // Cartão específico
        'card' => [
            'capture_method' => 'automatic', // ou 'manual' para autorização
            'statement_descriptor' => env('STRIPE_STATEMENT', 'CARINHO CUIDADOS'),
        ],

        // Connect para repasses (Stripe Connect)
        'connect' => [
            'enabled' => env('STRIPE_CONNECT_ENABLED', true),
            'account_type' => 'express', // express, standard, custom
        ],
        
        'timeout' => 30,
        'retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (Z-API)
    |--------------------------------------------------------------------------
    |
    | Integração com Z-API para notificações via WhatsApp.
    | Documentação: https://developer.z-api.io/
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
    | Integração com o sistema CRM para contratos e valores acordados.
    |
    */
    'crm' => [
        'base_url' => env('CRM_BASE_URL', 'https://crm.carinho.com.vc/api'),
        'token' => env('CRM_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Operação (Interno)
    |--------------------------------------------------------------------------
    |
    | Integração com o sistema de Operação para dados de serviços executados.
    |
    */
    'operacao' => [
        'base_url' => env('OPERACAO_BASE_URL', 'https://operacao.carinho.com.vc/api'),
        'token' => env('OPERACAO_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentos/LGPD (Interno)
    |--------------------------------------------------------------------------
    |
    | Integração com o sistema de Documentos para notas e comprovantes.
    |
    */
    'documentos' => [
        'base_url' => env('DOCUMENTOS_BASE_URL', 'https://documentos.carinho.com.vc/api'),
        'token' => env('DOCUMENTOS_TOKEN'),
        'timeout' => 15,
        'upload_timeout' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cuidadores (Interno)
    |--------------------------------------------------------------------------
    |
    | Integração com o sistema de Cuidadores para dados bancários e repasses.
    |
    */
    'cuidadores' => [
        'base_url' => env('CUIDADORES_BASE_URL', 'https://cuidadores.carinho.com.vc/api'),
        'token' => env('CUIDADORES_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrações Hub (Interno)
    |--------------------------------------------------------------------------
    |
    | Hub central de integrações para eventos e automações.
    |
    */
    'integracoes' => [
        'base_url' => env('INTEGRACOES_BASE_URL', 'https://integracoes.carinho.com.vc/api'),
        'token' => env('INTEGRACOES_TOKEN'),
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nota Fiscal Eletrônica (futuro)
    |--------------------------------------------------------------------------
    |
    | Integração para emissão de NFS-e. Placeholder para futura integração.
    |
    */
    'nfse' => [
        'enabled' => env('NFSE_ENABLED', false),
        'provider' => env('NFSE_PROVIDER', 'enotas'), // enotas, nfse.io, focus
        'base_url' => env('NFSE_BASE_URL'),
        'api_key' => env('NFSE_API_KEY'),
        'timeout' => 30,
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
