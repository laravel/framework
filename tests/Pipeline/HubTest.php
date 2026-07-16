<?php

namespace Illuminate\Tests\Pipeline;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Hub;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HubTest extends TestCase
{
    public function testPipeSendsObjectThroughDefaultPipeline()
    {
        $hub = new Hub(new Container);

        $hub->defaults(function (Pipeline $pipeline, $object) {
            return $pipeline->send($object)->through([])->thenReturn();
        });

        $this->assertSame('foo', $hub->pipe('foo'));
    }

    public function testPipeSendsObjectThroughNamedPipeline()
    {
        $hub = new Hub(new Container);

        $hub->pipeline('named', function (Pipeline $pipeline, $object) {
            return $pipeline->send($object)->through([])->thenReturn();
        });

        $this->assertSame('foo', $hub->pipe('foo', 'named'));
    }

    public function testPipeThrowsExceptionForUndefinedPipeline()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pipeline [missing] is not defined.');

        (new Hub(new Container))->pipe('foo', 'missing');
    }
}
