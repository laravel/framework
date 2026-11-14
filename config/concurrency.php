<?php

use Illuminate\Support\Env;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Concurrency Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default concurrency driver that will be used
    | by Laravel's concurrency functions. By default, concurrent work will
    | be sent to isolated PHP processes which will return their results.
    |
    | Supported: "process", "fork", "sync"
    |
    */

    'default' => Env::get('CONCURRENCY_DRIVER', 'process'),

];
