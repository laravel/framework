<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Image Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default image processing driver that will be
    | used when manipulating or converting images. This driver is always
    | utilized unless another driver is explicitly specified instead.
    |
    | Supported: "gd", "imagick", "cloudflare"
    |
    */

    'default' => env('IMAGE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Image Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Cloudflare Images API credentials for the
    | "cloudflare" image driver. The prefix option is used to namespace
    | temporary uploads so that orphaned images may be pruned safely.
    |
    */

    'drivers' => [

        'cloudflare' => [
            'account_id' => env('IMAGE_CLOUDFLARE_ACCOUNT_ID'),
            'api_token' => env('IMAGE_CLOUDFLARE_API_TOKEN'),
            'prefix' => env('IMAGE_CLOUDFLARE_PREFIX', 'laravel-image'),
        ],

    ],

];
