<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Tests\Console\Fixtures\FakeEventMutex;
use Orchestra\Testbench\TestCase;
use Throwable;

class EventPingTest extends TestCase
{
    public function testPingRescuesTransferExceptions()
    {
        $handler = new class implements ExceptionHandler
        {
            public array $reported = [];

            public function report(Throwable $e)
            {
                $this->reported[] = $e;
            }

            public function shouldReport(Throwable $e)
            {
                return true;
            }

            public function render($request, Throwable $e)
            {
                //
            }

            public function renderForConsole($output, Throwable $e)
            {
                //
            }
        };

        $this->swap(ExceptionHandler::class, $handler);

        $httpMock = new HttpClient([
            'handler' => HandlerStack::create(
                new MockHandler([new Psr7Response(500)])
            ),
        ]);

        $this->swap(HttpClient::class, $httpMock);

        $event = new Event(new FakeEventMutex, 'php -i');

        $thenCalled = false;

        $event->pingBefore('https://httpstat.us/500')
            ->then(function () use (&$thenCalled) {
                $thenCalled = true;
            });

        $event->callBeforeCallbacks($this->app->make(Container::class));
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertTrue($thenCalled);
        $this->assertCount(1, $handler->reported);
        $this->assertInstanceOf(ServerException::class, $handler->reported[0]);
    }
}
