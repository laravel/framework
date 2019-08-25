<?php

namespace Illuminate\Tests\Pipeline;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Responsable;

class PipelineTest extends TestCase
{
    public function testPipelineBasicUsage()
    {
        $pipeTwo = function ($piped, $next) {
            $_SERVER['__test.pipe.two'] = $piped;

            return $next($piped);
        };

        $result = (new Pipeline(new Container))
                    ->send('foo')
                    ->through([PipelineTestPipeOne::class, $pipeTwo])
                    ->then(function ($piped) {
                        return $piped;
                    });

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);
        $this->assertEquals('foo', $_SERVER['__test.pipe.two']);

        unset($_SERVER['__test.pipe.one']);
        unset($_SERVER['__test.pipe.two']);
    }

    public function testMultiplePipelinesBackAndForthExecutionOrder()
    {
        $pipeTwo = function ($piped, $next) {
            $_SERVER['__test.pipeline'] = $_SERVER['__test.pipeline'].'_forward2';

            $value = $next($piped);

            $_SERVER['__test.pipeline'] = $_SERVER['__test.pipeline'].'_backward2';

            return $value;
        };

        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through([PipelineTestPipeBack::class, $pipeTwo])
            ->then(function ($piped) {
                $_SERVER['__test.pipeline'] = $_SERVER['__test.pipeline'].'_core';

                return $piped;
            });

        $this->assertEquals('foo', $result);
        $this->assertEquals('forward1_forward2_core_backward2_backward1', $_SERVER['__test.pipeline']);

        unset($_SERVER['__test.pipeline']);
    }

    public function testPipelineUsageWithObjects()
    {
        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through([new PipelineTestPipeOne])
            ->then(function ($piped) {
                return $piped;
            });

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }

    public function testPipelineUsageWithInvokableObjects()
    {
        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through([new PipelineTestPipeTwo])
            ->then(
                function ($piped) {
                    return $piped;
                }
            );

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }

    public function testPipelineUsageWithResponsableObjects()
    {
        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through([new PipelineTestPipeResponsable])
            ->then(
                function ($piped) {
                    return $piped;
                }
            );

        $this->assertEquals('bar', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.responsable']);

        unset($_SERVER['__test.pipe.responsable']);
    }

    public function testNextLayersOfOnionAreNotRunWhenOneLayerReturnsEarly()
    {
        $returnEarly = function ($piped, $next) {
            $_SERVER['__test.pipeline'] = $_SERVER['__test.pipeline'].'_forward2';

            return 'value_from_pipe';
        };

        $notCalled = function ($piped, $next) {
            $_SERVER['__test.pipeline'] = $_SERVER['__test.pipeline'].'_not_called';

            $value = $next($piped);

            $_SERVER['__test.pipeline'] = $_SERVER['__test.pipeline'].'_not_called';

            return $value;
        };

        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through([PipelineTestPipeBack::class, $returnEarly, $notCalled])
            ->then(function ($piped) {
                $_SERVER['__test.pipeline'] = $_SERVER['__test.pipeline'].'not_called';

                return $piped;
            });

        $this->assertEquals('value_from_pipe', $result);
        $this->assertEquals('forward1_forward2_backward1', $_SERVER['__test.pipeline']);

        unset($_SERVER['__test.pipeline']);
    }

    public function testPipelineUsageWithCallable()
    {
        $function = function ($piped, $next) {
            $_SERVER['__test.pipe.one'] = 'foo';

            return $next($piped);
        };

        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through([$function])
            ->then(
                function ($piped) {
                    return $piped;
                }
            );

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }

    public function testPipelineUsageWithInvokableClass()
    {
        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through([PipelineTestPipeTwo::class])
            ->then(
                function ($piped) {
                    return $piped;
                }
            );

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }

    public function testPipelineUsageWithParameters()
    {
        $parameters = ['one', 'two'];

        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through(PipelineTestParameterPipe::class.':'.implode(',', $parameters))
            ->then(function ($piped) {
                return $piped;
            });

        $this->assertEquals('foo', $result);
        $this->assertEquals($parameters, $_SERVER['__test.pipe.parameters']);

        unset($_SERVER['__test.pipe.parameters']);
    }

    public function testItCanWorkWithNoPipe()
    {
        $result = (new Pipeline(new Container))
            ->send('foo')
            ->through([])
            ->thenReturn();

        $this->assertEquals('foo', $result);
    }

    public function testPipelineViaChangesTheMethodBeingCalledOnThePipes()
    {
        $piped = function ($piped, $next) {
            return $next($piped) + 1;
        };

        $pipelineInstance = new Pipeline(new Container);
        $result = $pipelineInstance->send(0)
            ->through([PipelineTestPipeOne::class, new PipelineTestPipeDifferent, $piped])
            ->via('differentMethod')
            ->then(function ($piped) {
                return $piped + 1;
            });
        $this->assertEquals(4, $result);
    }

    public function testPipelineThrowsExceptionOnResolveWithoutContainer()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A container instance has not been passed to the Pipeline.');

        (new Pipeline)->send('data')
            ->through(PipelineTestPipeOne::class)
            ->then(function ($piped) {
                return $piped;
            });
    }

    public function testPipelineThenReturnMethodRunsPipelineThenReturnsPassable()
    {
        $result = (new Pipeline(new Container))
                    ->send('foo')
                    ->through([PipelineTestPipeOne::class])
                    ->thenReturn();

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }
}

class PipelineTestPipeBack
{
    public function handle($piped, $next)
    {
        $_SERVER['__test.pipeline'] = 'forward1';

        $value = $next($piped);

        $_SERVER['__test.pipeline'] = $_SERVER['__test.pipeline'].'_backward1';

        return $value;
    }
}

class PipelineTestPipeOne
{
    public function handle($piped, $next)
    {
        $_SERVER['__test.pipe.one'] = $piped;

        return $next($piped);
    }

    public function differentMethod($piped, $next)
    {
        return $next($piped) + 1;
    }
}

class PipelineTestPipeDifferent
{
    public function differentMethod($piped, $next)
    {
        return $next($piped) + 1;
    }
}

class PipeResponsable implements Responsable
{
    public function toResponse($request)
    {
        return 'bar';
    }
}

class PipelineTestPipeTwo
{
    public function __invoke($piped, $next)
    {
        $_SERVER['__test.pipe.one'] = $piped;

        return $next($piped);
    }
}

class PipelineTestPipeResponsable
{
    public function handle($piped, $next)
    {
        $_SERVER['__test.pipe.responsable'] = $piped;

        return new PipeResponsable;
    }
}

class PipelineTestParameterPipe
{
    public function handle($piped, $next, $parameter1 = null, $parameter2 = null)
    {
        $_SERVER['__test.pipe.parameters'] = [$parameter1, $parameter2];

        return $next($piped);
    }
}
