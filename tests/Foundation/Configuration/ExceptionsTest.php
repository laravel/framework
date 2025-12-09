<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Closure;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionsTest extends TestCase
{
    #[TestWith([HttpException::class])]
    #[TestWith([ModelNotFoundException::class])]
    public function testStopIgnoring(string $class)
    {
        $container = new Container;
        $exceptions = new Exceptions;

        $handler = new class($container) extends Handler
        {
            public function getDontReport(): array
            {
                return array_merge($this->dontReport, $this->internalDontReport);
            }
        };

        $this->assertContains($class, $handler->getDontReport());

        $exceptions = $exceptions->stopIgnoring($class);

        $exceptions->handle($handler);

        $this->assertInstanceOf(Exceptions::class, $exceptions);
        $this->assertNotContains($class, $handler->getDontReport());
    }

    #[TestWith([HttpException::class])]
    #[TestWith([ModelNotFoundException::class])]
    public function testStopIgnoringUsingArray(string $class)
    {
        $container = new Container;
        $exceptions = new Exceptions;

        $handler = new class($container) extends Handler
        {
            public function getDontReport(): array
            {
                return array_merge($this->dontReport, $this->internalDontReport);
            }
        };

        $this->assertContains($class, $handler->getDontReport());

        $exceptions = $exceptions->stopIgnoring([$class]);

        $exceptions->handle($handler);

        $this->assertInstanceOf(Exceptions::class, $exceptions);
        $this->assertNotContains($class, $handler->getDontReport());
    }

    public static function shouldRenderJsonDataProvider()
    {
        yield [null, false];
        yield [fn () => true, true];
        yield [fn () => false, false];
    }

    #[DataProvider('shouldRenderJsonDataProvider')]
    public function testShouldRenderJsonWhen(?Closure $given, bool $expects)
    {
        $exceptions = new Exceptions;
        $handler = new Handler(new Container);

        if (! is_null($given)) {
            $exceptions->shouldRenderJsonWhen($given);
        }

        $exceptions->handle($handler);

        $this->assertSame($expects, (fn () => $this->shouldReturnJson(new Request, new Exception()))->call($handler));
    }
}
