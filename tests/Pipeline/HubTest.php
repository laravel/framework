<?php

namespace Illuminate\Tests\Pipeline;

use Closure;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Hub;

class HubTest extends TestCase
{
    public function testHubBasicUsage()
    {
        $hub = new Hub(new Container);

        $hub->defaults(function($pipeline, $value) {
            return $pipeline->send($value)
                ->through(SimplePipe::class)
                ->thenReturn();
        });

        $result = $hub->pipe('foo');

        $this->assertEquals('foo', $result);
    }

    public function testReceivesCallablePipeline()
    {
        $hub = new Hub(new Container);

        $hub->pipeline('foo', function ($pipeline, $object) {
            return $pipeline
                ->send($object)
                ->through(SimplePipe::class)
                ->thenReturn();
        });

        $result = $hub->pipe('bar', 'foo');

        $this->assertEquals('bar', $result);
    }
}

class SimplePipe {
    public function handle($value, Closure $next)
    {
        return $next($value);
    }
}