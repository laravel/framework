<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Foundation\Mix;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Facade;
use JMac\Testing\DoubleInterface;
use JMac\Testing\Integrations\PHPUnit\PHPUnitUnexpectedCallException;
use Orchestra\Testbench\TestCase;
use stdClass;

class InteractsWithContainerTest extends TestCase
{
    protected function tearDown(): void
    {
        Facade::useMockery();

        parent::tearDown();
    }

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
        $this->app->instance(InstanceStub::class, new class extends InstanceStub
        {
            public function execute()
            {
                return 'bar';
            }
        });

        $this->assertSame('bar', $this->app->make(InstanceStub::class)->execute());

        $this->forgetMock(InstanceStub::class);
        $this->assertSame('foo', $this->app->make(InstanceStub::class)->execute());
    }

    public function testMockBindsAStrictDoubleWhenUsingDouble()
    {
        Facade::useDouble();

        $double = $this->mock(DoubleStub::class);
        $double->expects('execute')->returns('bar');

        $this->assertInstanceOf(DoubleInterface::class, $double);
        $this->assertSame($double, $this->app->make(DoubleStub::class));
        $this->assertSame('bar', $this->app->make(DoubleStub::class)->execute());

        $this->expectException(PHPUnitUnexpectedCallException::class);

        $this->app->make(DoubleStub::class)->other();
    }

    public function testMockPassesTheDoubleToTheCallbackWhenUsingDouble()
    {
        Facade::useDouble();

        $double = $this->mock(DoubleStub::class, function ($mock) {
            $mock->expects('execute')->returns('bar');
        });

        $this->assertSame('bar', $double->execute());
    }

    public function testPartialMockPassesThroughToTheRealMethodsWhenUsingDouble()
    {
        Facade::useDouble();

        $this->partialMock(DoubleStub::class)->allows('other')->returns('baz');

        $this->assertSame('foo', $this->app->make(DoubleStub::class)->execute());
        $this->assertSame('baz', $this->app->make(DoubleStub::class)->other());
    }

    public function testSpyRecordsCallsWhenUsingDouble()
    {
        Facade::useDouble();

        $spy = $this->spy(DoubleStub::class);

        $this->assertNull($this->app->make(DoubleStub::class)->execute());

        $spy->received('execute');
    }

    public function testMockResolvesTheClassOfANonClassAbstractWhenUsingDouble()
    {
        Facade::useDouble();

        $this->app->instance('my.service', new DoubleStub);

        $this->mock('my.service')->expects('execute')->returns('bar');

        $this->assertInstanceOf(DoubleStub::class, $this->app->make('my.service'));
        $this->assertSame('bar', $this->app->make('my.service')->execute());
    }
}

class DoubleStub
{
    public function execute()
    {
        return 'foo';
    }

    public function other()
    {
        return 'other';
    }
}

class InstanceStub
{
    public function execute()
    {
        return 'foo';
    }
}
