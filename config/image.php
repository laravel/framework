<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Image Driver
    |--------------------------------------------------------------------------
    |
    | TODO
    |
    | Supported drivers: "gd", "imagick", "cloudflare"
    |
    */

    'default' => env('IMAGE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Images
    |--------------------------------------------------------------------------
    |
    | TODO
    |
    */

    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_IMAGES_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_IMAGES_API_TOKEN'),
        'prefix' => env('CLOUDFLARE_IMAGES_PREFIX', 'laravel-image'),
    ],

];
