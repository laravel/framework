<?php

return [
    'custom_option' => 'filesystems',

    'default' => 'overwrite',

    'disks' => [
        'local' => [
            'overwrite' => true,
        ],

        'new' => [
            'merge' => true,
        ],
    ],
];
