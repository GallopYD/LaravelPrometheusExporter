<?php

return [

    'enable' => env('PROMETHEUS_ENABLE', true),

    'adapter' => env('PROMETHEUS_ADAPTER', 'apc'),

    'namespace' => 'app',

    'namespace_http' => 'http',

    'redis' => [
        'host' => env('PROMETHEUS_REDIS_HOST', '127.0.0.1'),
        'port' => env('PROMETHEUS_REDIS_PORT', 6379),
        'timeout' => 0.1,  // in seconds
        'read_timeout' => 10, // in seconds
        'persistent_connections' => false,
    ],

    'push_gateway' => [
        'address' => env('PROMETHEUS_PUSH_GATEWAY_ADDRESS', 'localhost:9091')
    ],

    'buckets_per_route' => null,

    //http请求标签KEY
    'http_label_keys' => [
        'app_name',
        'request_uri',
        'method',
        'status_code',
        'client',
        'version',
//        'ip'
    ],

    //用户操作标签KEY
    'user_label_keys' => [
        'app_name',
        'user_id',
//        'ip'
    ],

    //用户操作监听
    'user_watchers' => [
        'login' => [
            // url => method ( GET / POST / PUT / DELETE / ANY )
//            'api/login' => 'POST'
        ],
        'register' => [

        ],
        'order' => [

        ],
        'recharge' => [

        ]
    ]

];
