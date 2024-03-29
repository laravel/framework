<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Orchestra\Testbench\TestCase;

class WithoutEventDiscoveryTest extends TestCase
{
    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withoutEventDiscovery()
            ->create();
    }

    public function testDisablesEventDiscovery()
    {
        $this->assertFalse(
            collect($this->app->getProviders(EventServiceProvider::class))
                ->firstOrFail()
                ->shouldDiscoverEvents()
        );
    }
}
