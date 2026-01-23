<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuracoes do Sistema de Cuidadores
    |--------------------------------------------------------------------------
    |
    | Configuracoes especificas para o modulo de gestao de cuidadores.
    |
    */

    'subdomain' => env('CUIDADORES_SUBDOMAIN', 'cuidadores.carinho.com.vc'),

    /*
    |--------------------------------------------------------------------------
    | Triagem e Validacao
    |--------------------------------------------------------------------------
    */
    'triagem' => [
        'documentos_obrigatorios' => [
            'id',      // Documento de identidade
            'cpf',     // CPF
            'address', // Comprovante de endereco
        ],
        'documentos_opcionais' => [
            'certificate', // Certificado de curso
            'other',       // Outros documentos
        ],
        'experiencia_minima_anos' => 0,
        'max_file_size_mb' => 10,
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ativacao e Desativacao
    |--------------------------------------------------------------------------
    */
    'ativacao' => [
        'auto_ativar_apos_validacao' => false,
        'notificar_cuidador' => true,
        'notificar_admin' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Avaliacoes
    |--------------------------------------------------------------------------
    */
    'avaliacoes' => [
        'nota_minima' => 1,
        'nota_maxima' => 5,
        'nota_alerta' => 3, // Alerta para notas abaixo disso
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('CUIDADORES_CACHE_ENABLED', true),
        'ttl_seconds' => env('CUIDADORES_CACHE_TTL', 300), // 5 minutos
        'prefix' => 'cuidadores',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paginacao
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retencao de Dados (LGPD)
    |--------------------------------------------------------------------------
    */
    'retencao' => [
        'documentos_dias' => 365 * 5, // 5 anos
        'logs_acesso_dias' => 365,
        'historico_status_dias' => 365 * 2,
    ],
];
