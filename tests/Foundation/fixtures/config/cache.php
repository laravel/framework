<?php

return [
    'custom_option' => 'cache',

    'default' => 'overwrite',

    'stores' => [
        'array' => [
            'overwrite' => true,
        ],

        'new' => [
            'merge' => true,
        ],
    ],
];
