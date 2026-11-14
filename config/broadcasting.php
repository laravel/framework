<?php

use Illuminate\Support\Env;

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
    | Supported: "reverb", "pusher", "ably", "mercure", "redis", "log", "null"
    |
    */

    'default' => Env::get('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over WebSockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => Env::get('REVERB_APP_KEY'),
            'secret' => Env::get('REVERB_APP_SECRET'),
            'app_id' => Env::get('REVERB_APP_ID'),
            'options' => [
                'host' => Env::get('REVERB_HOST'),
                'port' => Env::get('REVERB_PORT', 443),
                'scheme' => Env::get('REVERB_SCHEME', 'https'),
                'useTLS' => Env::get('REVERB_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => Env::get('PUSHER_APP_KEY'),
            'secret' => Env::get('PUSHER_APP_SECRET'),
            'app_id' => Env::get('PUSHER_APP_ID'),
            'options' => [
                'cluster' => Env::get('PUSHER_APP_CLUSTER'),
                'host' => Env::get('PUSHER_HOST') ?: 'api-'.Env::get('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => Env::get('PUSHER_PORT', 443),
                'scheme' => Env::get('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => Env::get('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => Env::get('ABLY_KEY'),
        ],

        'mercure' => [
            'driver' => 'mercure',
            'url' => Env::get('MERCURE_URL'),
            'public_url' => Env::get('MERCURE_PUBLIC_URL'),
            'secret' => Env::get('MERCURE_JWT_SECRET'),
            'encryption_key' => Env::get('MERCURE_ENCRYPTION_KEY'),
            'claims' => [
                'iss' => Env::get('MERCURE_JWT_ISSUER'),
                'client_id' => Env::get('APP_NAME'),
            ],
            'cookie_name' => Env::get('MERCURE_COOKIE_NAME'),
            'subscribe_expiration' => (int) Env::get('MERCURE_SUBSCRIBE_EXPIRATION', 5),
            'async' => (bool) Env::get('MERCURE_ASYNC', false),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
