<?php

return [
    'definitions' => [],
    'connection' => env('SCHEDULING_QUEUE_CONNECTION', 'database'),
    'queue' => env('SCHEDULING_QUEUE', 'scheduled-actions'),
    'max_attempts' => 3,
];
