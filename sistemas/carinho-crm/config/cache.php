<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    */
    'default' => env('CACHE_STORE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    */
    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => env('CACHE_REDIS_CONNECTION', 'cache'),
            'lock_connection' => env('CACHE_REDIS_LOCK_CONNECTION', 'default'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => env('CACHE_PREFIX', 'carinho_crm'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Configurations (em segundos)
    |--------------------------------------------------------------------------
    */
    'ttl' => [
        'dashboard' => 300,      // 5 minutos
        'pipeline' => 60,       // 1 minuto
        'reports' => 600,       // 10 minutos
        'domains' => 3600,      // 1 hora
        'clients_list' => 120,  // 2 minutos
        'leads_list' => 60,     // 1 minuto
    ],
];
