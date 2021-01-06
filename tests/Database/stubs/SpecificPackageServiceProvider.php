<?php

namespace Illuminate\Tests\Database\stubs;

use Illuminate\Support\ServiceProvider;

class SpecificPackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations-test-dir');
    }

    public static function expectedDirectory(): string
    {
        return __DIR__ . '/database/migrations-test-dir';
    }
}
