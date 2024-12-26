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
    | Component Boolean Attributes
    |--------------------------------------------------------------------------
    |
    | This option determines which HTML attributes should be considered as
    | boolean attributes when rendering components. HTML attributes listed
    | here will not have their values rendered when the component is rendered.
    | Given values will be check start with given string or full string.
    | For example, 'x-foo' will be rendered as '<div x-foo></div>'
    |
    */
    'boolean_attributes' => [
        'x-', // Alpine.js
        'wire:', // Laravel Livewire
    ],
];
