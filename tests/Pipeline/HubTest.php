<?php

namespace Illuminate\Tests\Pipeline;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Hub;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HubTest extends TestCase
{
    private Hub $hub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hub = new Hub(new Container);
    }

    public function testPipeSendsObjectThroughDefaultPipeline(): void
    {
        $this->hub->defaults(function (Pipeline $pipeline, $object) {
            return $pipeline->send($object)->through([])->thenReturn();
        });

        $this->assertSame('foo', $this->hub->pipe('foo'));
    }

    public function testPipeSendsObjectThroughNamedPipeline(): void
    {
        $this->hub->pipeline('named', function (Pipeline $pipeline, $object) {
            return $pipeline->send($object)->through([])->thenReturn();
        });

        $this->assertSame('foo', $this->hub->pipe('foo', 'named'));
    }

    public function testPipeThrowsExceptionForUndefinedPipeline(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Pipeline [missing] is not defined.'));

        $this->hub->pipe('foo', 'missing');
    }
}
