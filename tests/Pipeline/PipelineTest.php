<?php

namespace Illuminate\Tests\Pipeline;

use PHPUnit\Framework\TestCase;
use Illuminate\Pipeline\Pipeline;

class PipelineTest extends TestCase
{
    public function testPipelineBasicUsage()
    {
        $pipeTwo = function ($piped, $next) {
            $_SERVER['__test.pipe.two'] = $piped;

            return $next($piped);
        };

        $result = (new Pipeline(new \Illuminate\Container\Container))
                    ->send('foo')
                    ->through(['Illuminate\Tests\Pipeline\PipelineTestPipeOne', $pipeTwo])
                    ->then(function ($piped) {
                        return $piped;
                    });

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);
        $this->assertEquals('foo', $_SERVER['__test.pipe.two']);

        unset($_SERVER['__test.pipe.one']);
        unset($_SERVER['__test.pipe.two']);
    }

    public function testPipelineUsageWithObjects()
    {
        $result = (new Pipeline(new \Illuminate\Container\Container))
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
        $result = (new Pipeline(new \Illuminate\Container\Container))
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

    public function testPipelineUsageWithCallable()
    {
        $function = function ($piped, $next) {
            $_SERVER['__test.pipe.one'] = 'foo';

            return $next($piped);
        };

        $result = (new Pipeline(new \Illuminate\Container\Container))
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
        $result = (new Pipeline(new \Illuminate\Container\Container))
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

        $result = (new Pipeline(new \Illuminate\Container\Container))
            ->send('foo')
            ->through('Illuminate\Tests\Pipeline\PipelineTestParameterPipe:'.implode(',', $parameters))
            ->then(function ($piped) {
                return $piped;
            });

        $this->assertEquals('foo', $result);
        $this->assertEquals($parameters, $_SERVER['__test.pipe.parameters']);

        unset($_SERVER['__test.pipe.parameters']);
    }

    public function testPipelineViaChangesTheMethodBeingCalledOnThePipes()
    {
        $pipelineInstance = new Pipeline(new \Illuminate\Container\Container);
        $result = $pipelineInstance->send('data')
            ->through('Illuminate\Tests\Pipeline\PipelineTestPipeOne')
            ->via('differentMethod')
            ->then(function ($piped) {
                return $piped;
            });
        $this->assertEquals('data', $result);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPipelineThrowsExceptionOnResolveWithoutContainer()
    {
        (new Pipeline)->send('data')
            ->through('Illuminate\Tests\Pipeline\PipelineTestPipeOne')
            ->then(function ($piped) {
                return $piped;
            });
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
        return $next($piped);
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

class PipelineTestParameterPipe
{
    public function handle($piped, $next, $parameter1 = null, $parameter2 = null)
    {
        $_SERVER['__test.pipe.parameters'] = [$parameter1, $parameter2];

        return $next($piped);
    }
}
