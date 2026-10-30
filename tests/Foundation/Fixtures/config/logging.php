<?php

return [
    'custom_option' => 'logging',

    'default' => 'overwrite',

    'channels' => [
        'stack' => [
            'overwrite' => true,
        ],

        'new' => [
            'merge' => true,
        ],
    ],
];
