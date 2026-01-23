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
        'nota_minima_ativo' => 2.5, // Nota minima para manter cuidador ativo
    ],

    /*
    |--------------------------------------------------------------------------
    | Controles Operacionais
    |--------------------------------------------------------------------------
    */
    'operacional' => [
        // Controle de carga de trabalho
        'max_weekly_hours' => env('CUIDADORES_MAX_WEEKLY_HOURS', 44),
        'overtime_alert_hours' => env('CUIDADORES_OVERTIME_ALERT', 40),
        
        // Controle de documentos
        'document_expiry_alert_days' => env('CUIDADORES_DOC_EXPIRY_ALERT', 30),
        
        // Controle de ocorrencias
        'max_incidents_for_review' => env('CUIDADORES_MAX_INCIDENTS', 3),
        'incident_review_period_days' => 90,
        
        // Idade minima
        'min_age_years' => 18,
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

    /*
    |--------------------------------------------------------------------------
    | Indicadores e Metricas
    |--------------------------------------------------------------------------
    */
    'indicadores' => [
        // Taxa de ocupacao considerada ideal (%)
        'target_occupancy_rate' => 80,
        
        // Tempo maximo aceitavel para reposicao (dias)
        'max_replacement_days' => 3,
        
        // Periodo para calculos de indicadores (dias)
        'metrics_period_days' => 30,
    ],
];
