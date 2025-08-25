<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;

class FoundationServiceProvidersTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [HeadServiceProvider::class];
    }

    public function testItCanBootServiceProviderRegisteredFromAnotherServiceProvider()
    {
        $this->assertTrue($this->app['tail.registered']);
        $this->assertTrue($this->app['tail.booted']);
    }
}

class HeadServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->app->register(TailServiceProvider::class);
    }
}

class TailServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['tail.registered'] = true;
    }

    public function boot()
    {
        $this->app['tail.booted'] = true;
    }
}
