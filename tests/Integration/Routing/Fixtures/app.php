<?php

namespace Illuminate\Tests\Integration\Routing\Fixtures;

use Illuminate\Foundation\Application;

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
