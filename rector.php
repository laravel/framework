<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use Rector\Config\RectorConfig;
use Rector\Transform\Rector\FuncCall\FuncCallToNewRector;
use Rector\Transform\Rector\FuncCall\FuncCallToStaticCallRector;
use Rector\Transform\ValueObject\FuncCallToStaticCall;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/types',
    ])
    ->withRootFiles()
    ->withConfiguredRule(FuncCallToNewRector::class, [
        'collect' => Collection::class,
    ])
    ->withConfiguredRule(FuncCallToStaticCallRector::class, [
        new FuncCallToStaticCall('env', Env::class, 'get'),
        new FuncCallToStaticCall('now', Carbon::class, 'now'),
    ])
    ->withSkip([
        FuncCallToStaticCallRector::class => [
            __DIR__.'/config',
        ],
        'tests/Foundation/fixtures/bad-syntax-strategy.php',
    ]);
