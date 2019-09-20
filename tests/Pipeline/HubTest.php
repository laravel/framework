<?php

namespace Illuminate\Tests\Pipeline;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Hub;
use LogicException;
use PHPUnit\Framework\TestCase;

class HubTest extends TestCase
{
    public function testHubReceivesDefault()
    {
        $hub = new Hub(new Container);

        $hub->defaults(function ($pipeline, $passable) {
            return $pipeline->send($passable)
                ->through(PipelineEmpty::class)
                ->thenReturn();
        });

        $this->assertTrue($hub->pipe(true));
    }

    public function testHubReceivesNamedPipe()
    {
        $hub = new Hub(new Container);

        $hub->pipeline('test-pipeline', function ($pipeline, $passable) {
            return $pipeline->send($passable)
                ->through(PipelineEmpty::class)
                ->thenReturn();
        });

        $hub->defaults(function ($pipeline, $passable) {
            return $pipeline->send($passable)
                ->through(PipelineFoo::class)
                ->thenReturn();
        });

        $this->assertEquals('foo', $hub->pipe('foo', 'test-pipeline'));
        $this->assertEquals('foo', $hub->pipe('bar'));
    }

    public function testHubUsesNamedMethod()
    {
        $hub = new class(new Container) extends Hub {
            protected function pipelineFooBar($pipeline, $passable)
            {
                return $pipeline->send($passable)->through(PipelineEmpty::class)->thenReturn();
            }
        };

        $hub->pipeline('test-pipeline', function ($pipeline, $passable) {
            return $pipeline->send($passable)
                ->through(PipelineEmpty::class)
                ->thenReturn();
        });

        $hub->defaults(function ($pipeline, $passable) {
            return $pipeline->send($passable)
                ->through(PipelineFoo::class)
                ->thenReturn();
        });

        $this->assertEquals('foo', $hub->pipe('foo', 'test-pipeline'));
        $this->assertEquals('foo', $hub->pipe('bar'));
        $this->assertEquals('baz', $hub->pipe('baz', 'foo-bar'));
        $this->assertEquals('baz', $hub->pipe('baz', 'foo_bar'));
        $this->assertEquals('baz', $hub->pipe('baz', 'fooBar'));
        $this->assertEquals('baz', $hub->pipe('baz', 'FooBar'));
    }

    public function testHubFailsIfPipelineDoesntExists()
    {
        $this->expectException(LogicException::class);

        $hub = new class(new Container) extends Hub {
            protected function pipelineNamed($pipeline, $passable)
            {
                return $pipeline->send($passable)->through(PipelineEmpty::class)->thenReturn();
            }
        };

        $hub->pipeline('test-pipeline', function ($pipeline, $passable) {
            return $pipeline->send($passable)
                ->through(PipelineEmpty::class)
                ->thenReturn();
        });

        $hub->defaults(function ($pipeline, $passable) {
            return $pipeline->send($passable)
                ->through(PipelineFoo::class)
                ->thenReturn();
        });

        $hub->pipe('foo', 'no-pipe');
    }
}

class PipelineEmpty
{
    public function handle($piped, $next)
    {
        return $next($piped);
    }
}

class PipelineFoo
{
    public function handle($piped, $next)
    {
        return $next('foo');
    }
}
