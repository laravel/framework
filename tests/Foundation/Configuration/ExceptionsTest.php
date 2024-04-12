<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionsTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testStopIgnoring()
    {
        $container = new Container;
        $exceptions = new Exceptions($handler = new class($container) extends Handler
        {
            public function getDontReport(): array
            {
                return array_merge($this->dontReport, $this->internalDontReport);
            }
        });

        $this->assertContains(HttpException::class, $handler->getDontReport());
        $exceptions = $exceptions->stopIgnoring(HttpException::class);
        $this->assertInstanceOf(Exceptions::class, $exceptions);
        $this->assertNotContains(HttpException::class, $handler->getDontReport());

        $this->assertContains(ModelNotFoundException::class, $handler->getDontReport());
        $exceptions->stopIgnoring([ModelNotFoundException::class]);
        $this->assertNotContains(ModelNotFoundException::class, $handler->getDontReport());
    }

    public function testShouldRenderJsonWhen()
    {
        $exceptions = new Exceptions(new Handler(new Container));

        $shouldReturnJson = (fn () => $this->shouldReturnJson(new Request, new Exception()))->call($exceptions->handler);
        $this->assertFalse($shouldReturnJson);

        $exceptions->shouldRenderJsonWhen(fn () => true);
        $shouldReturnJson = (fn () => $this->shouldReturnJson(new Request, new Exception()))->call($exceptions->handler);
        $this->assertTrue($shouldReturnJson);

        $exceptions->shouldRenderJsonWhen(fn () => false);
        $shouldReturnJson = (fn () => $this->shouldReturnJson(new Request, new Exception()))->call($exceptions->handler);
        $this->assertFalse($shouldReturnJson);
    }
}
