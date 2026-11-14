<?php

use Illuminate\Support\Env;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => Env::get('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => Env::get('RESEND_KEY'),
    ],

    'ses' => [
        'key' => Env::get('AWS_ACCESS_KEY_ID'),
        'secret' => Env::get('AWS_SECRET_ACCESS_KEY'),
        'region' => Env::get('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => Env::get('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => Env::get('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
