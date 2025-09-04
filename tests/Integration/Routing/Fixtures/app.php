<?php

namespace Illuminate\Tests\Integration\Routing\Fixtures;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

if (! class_exists(AppCache::class)) {
    class AppCache
    {
        public static $app;
    }
}

if (isset($refresh)) {
    return AppCache::$app = Application::configure(basePath: __DIR__)->create();
} else {
    return AppCache::$app ??= Application::configure(basePath: __DIR__)->create();
}
