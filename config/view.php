<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Ignore view cache timestamps
    |--------------------------------------------------------------------------
    |
    | This option determines whether timestamps of cached views should be
    | ignored. You should only enable this if you have pre-compiled all views
    | with `artisan view:cache`. Whenever a view templates changes, you must
    | run this command again to update the view cache.
    |
    */

    'ignore_cache_timestamps' => env(
        'VIEW_CACHE_IGNORE_TIMESTAMPS',
        false
    ),

];
