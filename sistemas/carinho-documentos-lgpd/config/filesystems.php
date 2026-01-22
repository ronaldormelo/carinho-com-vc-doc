<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */
    'default' => env('FILESYSTEM_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Amazon S3 - Armazenamento Principal de Documentos
        |--------------------------------------------------------------------------
        |
        | Configuracao para armazenamento seguro no AWS S3.
        | Todos os documentos sao armazenados com criptografia server-side.
        |
        | Estrutura de pastas:
        | - clients/{client_id}/
        | - caregivers/{caregiver_id}/
        | - contracts/{year}/{month}/
        | - templates/
        | - exports/
        |
        */
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'sa-east-1'),
            'bucket' => env('AWS_BUCKET', 'carinho-documentos'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => true,

            // Criptografia server-side
            'options' => [
                'ServerSideEncryption' => 'AES256',
                'CacheControl' => 'max-age=0, no-cache, no-store, must-revalidate',
            ],

            // Visibilidade padrao (private para seguranca)
            'visibility' => 'private',
        ],

        /*
        |--------------------------------------------------------------------------
        | Disk Temporario para Processamento
        |--------------------------------------------------------------------------
        */
        'temp' => [
            'driver' => 'local',
            'root' => storage_path('app/temp'),
            'throw' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
