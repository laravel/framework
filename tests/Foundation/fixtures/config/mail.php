<?php

return [
    'custom_option' => 'mail',

    'default' => 'overwrite',

    'mailers' => [
        'smtp' => [
            'overwrite' => true,
        ],

        'new' => [
            'merge' => true,
        ],
    ],
];
