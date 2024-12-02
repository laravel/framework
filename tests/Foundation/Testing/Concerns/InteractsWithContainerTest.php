<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Foundation\Mix;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Orchestra\Testbench\TestCase;
use stdClass;

class InteractsWithContainerTest extends TestCase
{
    public function testWithoutViteBindsEmptyHandlerAndReturnsInstance()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)(['resources/js/app.js'])->toHtml());
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteHandlesReactRefresh()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)->reactRefresh());
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteHandlesAsset()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)->asset('path/to/asset.png'));
        $this->assertSame($this, $instance);
    }

    public function testWithViteRestoresOriginalHandlerAndReturnsInstance()
    {
        $handler = new stdClass;
        $this->app->instance(Vite::class, $handler);

        $this->withoutVite();
        $instance = $this->withVite();

        $this->assertSame($handler, resolve(Vite::class));
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteReturnsEmptyArrayForPreloadedAssets(): void
    {
        $instance = $this->withoutVite();

        $this->assertSame([], app(Vite::class)->preloadedAssets());
        $this->assertSame($this, $instance);
    }

    public function testWithoutMixBindsEmptyHandlerAndReturnsInstance()
    {
        $instance = $this->withoutMix();

        $this->assertSame('', (string) mix('path/to/asset.png'));
        $this->assertSame($this, $instance);
    }

    public function testWithMixRestoresOriginalHandlerAndReturnsInstance()
    {
        $handler = new stdClass;
        $this->app->instance(Mix::class, $handler);

        $this->withoutMix();
        $instance = $this->withMix();

        $this->assertSame($handler, resolve(Mix::class));
        $this->assertSame($this, $instance);
    }

    public function testWithoutDefer()
    {
        $called = [];
        defer(function () use (&$called) {
            $called[] = 1;
        });
        $this->assertSame([], $called);

        $instance = $this->withoutDefer();
        defer(function () use (&$called) {
            $called[] = 2;
        });
        $this->assertSame([2], $called);
        $this->assertSame($this, $instance);

        $this->withDefer();
        $this->assertSame([2], $called);
        $this->app[DeferredCallbackCollection::class]->invoke();
        $this->assertSame([2, 1], $called);
    }

    public function testForgetMock()
    {
        $this->mock(InstanceStub::class)
            ->shouldReceive('execute')
            ->once()
            ->andReturn('bar');

        $this->assertSame('bar', $this->app->make(InstanceStub::class)->execute());

        $this->forgetMock(InstanceStub::class);
        $this->assertSame('foo', $this->app->make(InstanceStub::class)->execute());
    }
}

class InstanceStub
{
    public function execute()
    {
        return 'foo';
    }
}
