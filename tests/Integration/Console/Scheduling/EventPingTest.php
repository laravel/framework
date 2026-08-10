<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Orchestra\Testbench\TestCase;

class EventPingTest extends TestCase
{
    public function testPingRescuesTransferExceptions()
    {
        $this->spy(ExceptionHandler::class)
            ->expects('report')

            ->with(Mockery::type(ServerException::class));

        $httpMock = new HttpClient([
            'handler' => HandlerStack::create(
                new MockHandler([new Psr7Response(500)])
            ),
        ]);

        $this->swap(HttpClient::class, $httpMock);

        $event = new Event(Mockery::mock(EventMutex::class), 'php -i');

        $thenCalled = false;

        $event->pingBefore('https://httpstat.us/500')
            ->then(function () use (&$thenCalled) {
                $thenCalled = true;
            });

        $event->callBeforeCallbacks($this->app->make(Container::class));
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertTrue($thenCalled);
    }
}
