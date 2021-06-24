<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Foundation\Mix;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use stdClass;

class InteractsWithContainerTest extends TestCase
{
    public function testWithoutMixBindsEmptyHandlerAndReturnsInstance()
    {
        $instance = $this->withoutMix();

        $this->assertSame('', mix('path/to/asset.png'));
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

    public function testSingletonBoundInstancesCanBeResolved()
    {
        $this->singletonInstance('foo', 'bar');

        $this->assertEquals('bar', $this->app->make('foo'));
        $this->assertEquals('bar', $this->app->make('foo', ['with' => 'params']));
    }

    public function testSingletonRebindsAlias()
    {
        $this->app->instance('request', Request::create('/example'));

        $this->mock(Request::class, function (MockInterface $mock) {
            //
        });

        $this->assertInstanceOf(MockInterface::class, resolve(Request::class));
        $this->assertInstanceOf(MockInterface::class, resolve('request'));
    }
}
