<?php

namespace Illuminate\Tests\Foundation\Bootstrap\Testing\Concerns;

use Illuminate\Foundation\Mix;
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

    public function testForgetMock()
    {
        $this->mock(IntanceStub::class)
            ->shouldReceive('execute')
            ->once()
            ->andReturn('bar');

        $this->assertSame('bar', $this->app->make(IntanceStub::class)->execute());

        $this->forgetMock(IntanceStub::class);
        $this->assertSame('foo', $this->app->make(IntanceStub::class)->execute());
    }

    public function testPartialMockSimple()
    {
        $this->partialMock(IntanceStub::class)
            ->shouldReceive('execute')
            ->andReturn('bar');

        $this->assertSame('bar', $this->app->make(IntanceStub::class)->execute());
    }

    public function testPartialMockConstructor()
    {
        $this->partialMock(IntanceStub::class)
            ->shouldReceive('execute')
            ->andReturn('bar');

        $this->partialMock([PartialMockStub::class, ['bar']], function ($mock) {
            $mock
                ->shouldReceive('getGreeting')
                ->andReturn('Hi');
        });

        $this->assertSame('Hi bar', $this->app->make(PartialMockStub::class)->greet());
    }
}

class IntanceStub
{
    public function execute()
    {
        return 'foo';
    }
}

class PartialMockStub
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function greet()
    {
        return $this->getGreeting() . ' ' . $this->name;
    }

    public function getGreeting()
    {
        return 'Hello';
    }
}
