<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | Aqui definimos qual store de cache será usado por padrão na aplicação.
    | Usamos a variável de ambiente CACHE_STORE para permitir sobrescrita,
    | caindo por padrão em "redis".
    |
    */
    'default' => env('CACHE_STORE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Definição dos drivers de cache disponíveis. Para o site, utilizamos
    | principalmente Redis, mas mantemos também array e file para cenários
    | específicos (testes, fallback local, etc.).
    |
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
    |
    | Prefixo padrão para todas as chaves de cache desta aplicação, para evitar
    | colisão com outros sistemas utilizando o mesmo Redis.
    |
    */
    'prefix' => env('CACHE_PREFIX', 'carinho_site'),
];

