<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\WarmerCollection;
use Orchestra\Testbench\TestCase;

class WarmAppTest extends TestCase
{
    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton(KernelContract::class, fn ($app) => new class ($app, $app->make(Dispatcher::class)) extends Kernel {
            public function commands()
            {
                $this->command('warm.command', function () {
                    Cache::put('warm.command', true, now()->addDay());
                });
            }

            protected function warm(Repository $cache)
            {
                $cache->put('warm', true, now()->addDay());
            }
        });
    }

    public function testItCanWarmAppViaKernel()
    {
        $this->assertNull(Cache::get('warm'));

        Artisan::call('warm');

        $this->assertTrue(Cache::get('warm'));
    }

    public function testPackagesCanBindClosuresToWarm()
    {
        $this->app->register(new class($this->app) extends ServiceProvider {
            public function register()
            {
                //
            }

            public function boot()
            {
                $this->registerWarmer('spatie/package', fn (Repository $cache) => $cache->put('warm.package', true, now()->addDay()));
            }
        });

        $this->assertNull(Cache::get('warm.package'));

        Artisan::call('warm');

        $this->assertTrue(Cache::get('warm.package'));
    }

    public function testItCanRunArtisanCommandsInWarmer()
    {
        $this->app->register(new class($this->app) extends ServiceProvider {
            public function register()
            {
                //
            }

            public function boot()
            {
                $this->registerWarmer('spatie/package', fn () => Artisan::call('warm.command'));
            }
        });

        $this->assertNull(Cache::get('warm.command'));

        Artisan::call('warm');

        $this->assertTrue(Cache::get('warm.command'));
    }

    public function testItCanExcludeRegisteredWarmers()
    {
        $this->app->instance(WarmerCollection::class, new WarmerCollection([
            'foo' => fn () => Cache::put('warm.foo', true, now()->addDay()),
            'bar' => fn () => Cache::put('warm.bar', true, now()->addDay()),
            'baz' => fn () => Cache::put('warm.baz', true, now()->addDay()),
        ]));

        $this->assertNull(Cache::get('warm.foo'));
        $this->assertNull(Cache::get('warm.bar'));
        $this->assertNull(Cache::get('warm.baz'));

        Artisan::call('warm --exclude=bar,baz');

        $this->assertTrue(Cache::get('warm.foo'));
        $this->assertNull(Cache::get('warm.bar'));
        $this->assertNull(Cache::get('warm.baz'));
    }

    public function testItCanRunSpecificWarmersOnly()
    {
        $this->app->instance(WarmerCollection::class, new WarmerCollection([
            'foo' => fn () => Cache::put('warm.foo', true, now()->addDay()),
            'bar' => fn () => Cache::put('warm.bar', true, now()->addDay()),
            'baz' => fn () => Cache::put('warm.baz', true, now()->addDay()),
        ]));

        $this->assertNull(Cache::get('warm.foo'));
        $this->assertNull(Cache::get('warm.bar'));
        $this->assertNull(Cache::get('warm.baz'));

        Artisan::call('warm --only=bar,baz');

        $this->assertNull(Cache::get('warm.foo'));
        $this->assertTrue(Cache::get('warm.bar'));
        $this->assertTrue(Cache::get('warm.baz'));
    }

    public function testItCanRunNoRegisteredWarmers()
    {
        $this->app->instance(WarmerCollection::class, new WarmerCollection([
            'foo' => fn () => Cache::put('warm.foo', true, now()->addDay()),
            'bar' => fn () => Cache::put('warm.bar', true, now()->addDay()),
            'baz' => fn () => Cache::put('warm.baz', true, now()->addDay()),
        ]));

        $this->assertNull(Cache::get('warm.foo'));
        $this->assertNull(Cache::get('warm.bar'));
        $this->assertNull(Cache::get('warm.baz'));

        Artisan::call('warm --only=');

        $this->assertNull(Cache::get('warm.foo'));
        $this->assertNull(Cache::get('warm.bar'));
        $this->assertNull(Cache::get('warm.baz'));
    }

    public function testMixOfIncludeExclude()
    {
        $this->app->instance(WarmerCollection::class, new WarmerCollection([
            'foo' => fn () => Cache::put('warm.foo', true, now()->addDay()),
            'bar' => fn () => Cache::put('warm.bar', true, now()->addDay()),
            'baz' => fn () => Cache::put('warm.baz', true, now()->addDay()),
        ]));

        $this->assertNull(Cache::get('warm.foo'));
        $this->assertNull(Cache::get('warm.bar'));
        $this->assertNull(Cache::get('warm.baz'));

        Artisan::call('warm --only=warm.foo --exclude=warm.foo');

        $this->assertNull(Cache::get('warm.foo'));
        $this->assertNull(Cache::get('warm.bar'));
        $this->assertNull(Cache::get('warm.baz'));
    }
}
