<?php

use Illuminate\Support\Str;

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => 'horizon',
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'carinho-integracoes'), '_').'_horizon:'),
    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 128,

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['integrations-high', 'integrations', 'notifications', 'integrations-low', 'integrations-retry'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-high' => [
                'connection' => 'redis',
                'queue' => ['integrations-high'],
                'balance' => 'auto',
                'minProcesses' => 2,
                'maxProcesses' => 5,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 30,
            ],
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['integrations', 'notifications'],
                'balance' => 'auto',
                'minProcesses' => 3,
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-low' => [
                'connection' => 'redis',
                'queue' => ['integrations-low', 'integrations-retry'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 5,
                'timeout' => 300,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['integrations-high', 'integrations', 'notifications', 'integrations-low', 'integrations-retry'],
                'balance' => 'simple',
                'processes' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
        ],
    ],
];
