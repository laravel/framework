<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;
use Boundwize\StructArmed\Preset\Presets\Psr1Preset;

return Architecture::define()
    // apply on src for now to ease review
    ->layer('Source', 'src')

    // Primitives shared by every component.
    ->layer('Contracts', 'src/Illuminate/Contracts')
    ->layer('Macroable', 'src/Illuminate/Macroable')
    ->layer('Conditionable', 'src/Illuminate/Conditionable')
    ->layer('Collections', 'src/Illuminate/Collections')
    ->layer('Reflection', 'src/Illuminate/Reflection')
    ->layer('Support', 'src/Illuminate/Support', [
        'src/Illuminate/Support/DefaultProviders.php',
        'src/Illuminate/Support/Facades',
        'src/Illuminate/Support/Testing',
    ])
    ->layer('Config', 'src/Illuminate/Config')
    ->layer('Container', 'src/Illuminate/Container')
    ->layer('JsonSchema', 'src/Illuminate/JsonSchema')

    // Self-contained components built on Support.
    ->layer('Pipeline', 'src/Illuminate/Pipeline')
    ->layer('Process', 'src/Illuminate/Process')
    ->layer('Encryption', 'src/Illuminate/Encryption')
    ->layer('Hashing', 'src/Illuminate/Hashing')
    ->layer('Cookie', 'src/Illuminate/Cookie')
    ->layer('Redis', 'src/Illuminate/Redis')
    ->layer('Concurrency', 'src/Illuminate/Concurrency')
    ->layer('Translation', 'src/Illuminate/Translation')
    ->layer('Filesystem', 'src/Illuminate/Filesystem')
    ->layer('Image', 'src/Illuminate/Image')
    ->layer('Cache', 'src/Illuminate/Cache')
    ->layer('Log', 'src/Illuminate/Log')
    ->layer('Console', 'src/Illuminate/Console', 'src/Illuminate/Console/Scheduling')
    ->layer('Scheduling', 'src/Illuminate/Console/Scheduling')

    // Persistence and the HTTP stack.
    ->layer('Database', 'src/Illuminate/Database')
    ->layer('Pagination', 'src/Illuminate/Pagination')
    ->layer('Http', 'src/Illuminate/Http')
    ->layer('Session', 'src/Illuminate/Session')
    ->layer('Routing', 'src/Illuminate/Routing')
    ->layer('View', 'src/Illuminate/View')
    ->layer('Validation', 'src/Illuminate/Validation')

    // Messaging.
    ->layer('Bus', 'src/Illuminate/Bus')
    ->layer('Queue', 'src/Illuminate/Queue')
    ->layer('Events', 'src/Illuminate/Events')
    ->layer('Broadcasting', 'src/Illuminate/Broadcasting')
    ->layer('Mail', 'src/Illuminate/Mail')
    ->layer('Notifications', 'src/Illuminate/Notifications')
    ->layer('Auth', 'src/Illuminate/Auth')

    // Testing helpers and the application glue.
    ->layer('Testing', 'src/Illuminate/Testing')
    ->layer('Fakes', 'src/Illuminate/Support/Testing')
    ->layer('Foundation', 'src/Illuminate/Foundation')
    ->layer('DefaultProviders', 'src/Illuminate/Support/DefaultProviders.php')
    ->layer('Facades', 'src/Illuminate/Support/Facades')

    ->ruleset([
        // Primitives shared by every component.
        'Contracts' => ['Database', 'Http', 'Image', 'Support', 'Validation'],
        'Macroable' => [],
        'Conditionable' => [],
        'Collections' => ['Conditionable', 'Contracts', 'Macroable', 'Support', 'Database', 'Http'],
        'Reflection' => ['Collections'],
        'Support' => ['+Collections', 'Reflection', 'Container', 'Console', 'DefaultProviders', 'Facades', 'Filesystem', 'Http', 'Queue', 'View'],
        'Config' => ['+Collections'],
        'Container' => ['Collections', 'Contracts', 'Reflection', 'Log'],
        'JsonSchema' => ['Contracts'],

        // Self-contained components built on Support.
        'Pipeline' => ['+Support'],
        'Process' => ['+Support'],
        'Encryption' => ['+Support'],
        'Hashing' => ['+Support'],
        'Cookie' => ['+Support'],
        'Redis' => ['+Support'],
        'Translation' => ['+Support'],
        'Concurrency' => ['+Support', 'Process'],
        'Filesystem' => ['+Support', 'Image'],
        'Image' => ['+Support'],
        'Cache' => ['+Support', 'Database', 'Redis'],
        'Log' => ['+Support', 'Database'],
        'Console' => ['+Support', 'Cache', 'Scheduling'],
        'Scheduling' => ['+Console', 'Bus', 'Log'],

        // Persistence and the HTTP stack.
        'Database' => ['+Support', 'Broadcasting', 'Events', 'Pagination'],
        'Pagination' => ['+Support', 'Database'],
        'Http' => ['+Support', 'Database', 'Image', 'Pagination', 'Session'],
        'Session' => ['+Support', 'Auth', 'Database', 'Routing'],
        'Routing' => ['+Support', 'Auth', 'Cache', 'Database', 'Pipeline', 'Redis', 'Session'],
        'View' => ['+Support', 'Database', 'Foundation'],
        'Validation' => ['+Support', 'Database', 'Foundation', 'Translation'],

        // Messaging.
        'Bus' => ['+Support', 'Database', 'Fakes', 'Foundation', 'Pipeline'],
        'Queue' => ['+Bus', 'Cache', 'Events', 'Log', 'Redis'],
        'Events' => ['+Support', 'Bus'],
        'Broadcasting' => ['+Support', 'Bus', 'Cookie', 'Foundation', 'Redis', 'Routing'],
        'Mail' => ['+Support', 'Bus', 'Config', 'Log', 'Testing'],
        'Notifications' => ['+Support', 'Broadcasting', 'Bus', 'Config', 'Database', 'Events', 'Mail'],
        'Auth' => ['+Support', 'Cache', 'Database', 'Notifications'],

        // Testing helpers and the application glue.
        'Testing' => ['+Support', 'Cookie', 'Database', 'Foundation'],
        'Fakes' => ['+Support', 'Bus', 'Database', 'Events', 'Foundation', 'Mail', 'Notifications', 'Testing'],
        'Foundation' => [
            'Auth', 'Broadcasting', 'Bus', 'Cache', 'Collections', 'Concurrency', 'Conditionable', 'Config', 'Console',
            'Container', 'Contracts', 'Cookie', 'Database', 'DefaultProviders', 'Encryption', 'Events', 'Facades', 'Fakes',
            'Filesystem', 'Hashing', 'Http', 'Image', 'JsonSchema', 'Log', 'Macroable', 'Mail', 'Notifications',
            'Pagination', 'Pipeline', 'Process', 'Queue', 'Redis', 'Reflection', 'Routing', 'Scheduling', 'Session',
            'Support', 'Testing', 'Translation', 'Validation', 'View',
        ],
        'DefaultProviders' => ['+Foundation'],
        'Facades' => ['+Foundation'],
    ])

    ->skip([
        // skip for now as change on src may cause bc break
        Psr1Preset::CLASS_CONSTANTS_MUST_BE_UPPER_CASE,
        Psr1Preset::METHODS_MUST_BE_CAMEL_CASE,
    ])
    ->withPresets(Preset::PER());
