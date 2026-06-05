<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'pusher'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

    'pusher' => [
        'driver' => 'pusher',
        'key' => '123456',
        'secret' => '123456',
        'app_id' => '123456',
        'options' => [
            'cluster' => 'mt1',
            'useTLS' => false,
            'scheme' => 'http',
            'host' => '127.0.0.1', // Assurez-vous que c'est bien l'IP d'écoute
            'port' => 6001,
            'encrypted' => false,
            // AJOUTEZ CETTE LIGNE : cela force l'utilisation du port 6001 sans chercher ailleurs
            'curl_options' => [
                CURLOPT_PORT => 6001,
            ],
        ],
        'client_options' => [],
    ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
