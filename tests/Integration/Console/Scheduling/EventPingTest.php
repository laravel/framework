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
use Mockery as m;
use Orchestra\Testbench\TestCase;

class EventPingTest extends TestCase
{
    public function testPingRescuesTransferExceptions()
    {
        $this->spy(ExceptionHandler::class)
            ->shouldReceive('report')
            ->once()
            ->with(m::type(ServerException::class));

        $httpMock = new HttpClient([
            'handler' => HandlerStack::create(
                new MockHandler([new Psr7Response(500)])
            ),
        ]);

        $this->swap(HttpClient::class, $httpMock);

        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $thenCalled = false;

        $event->pingBefore('https://httpstat.us/500')
            ->then(function () use (&$thenCalled) {
                $thenCalled = true;
            });

        $event->callBeforeCallbacks($this->app->make(Container::class));
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertTrue($thenCalled);
    }

    public function testPingBeforeIfPingsTheUrlWhenTheConditionIsTrue()
    {
        $mock = $this->swapHttpClientWithMock();

        $event = $this->newEvent();

        $event->pingBeforeIf(true, 'https://example.com/ping');
        $event->callBeforeCallbacks($this->app->make(Container::class));

        $this->assertPinged($mock);
    }

    public function testPingBeforeIfDoesNotPingTheUrlWhenTheConditionIsFalse()
    {
        $mock = $this->swapHttpClientWithMock();

        $event = $this->newEvent();

        $event->pingBeforeIf(false, 'https://example.com/ping');
        $event->callBeforeCallbacks($this->app->make(Container::class));

        $this->assertNull($mock->getLastRequest());
    }

    public function testThenPingIfPingsTheUrlWhenTheConditionIsTrue()
    {
        $mock = $this->swapHttpClientWithMock();

        $event = $this->newEvent();

        $event->thenPingIf(true, 'https://example.com/ping');
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertPinged($mock);
    }

    public function testThenPingIfDoesNotPingTheUrlWhenTheConditionIsFalse()
    {
        $mock = $this->swapHttpClientWithMock();

        $event = $this->newEvent();

        $event->thenPingIf(false, 'https://example.com/ping');
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertNull($mock->getLastRequest());
    }

    public function testPingOnSuccessIfPingsTheUrlWhenTheConditionIsTrue()
    {
        $mock = $this->swapHttpClientWithMock();

        $event = $this->newEvent();
        $event->exitCode = 0;

        $event->pingOnSuccessIf(true, 'https://example.com/ping');
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertPinged($mock);
    }

    public function testPingOnSuccessIfDoesNotPingTheUrlWhenTheConditionIsFalse()
    {
        $mock = $this->swapHttpClientWithMock();

        $event = $this->newEvent();
        $event->exitCode = 0;

        $event->pingOnSuccessIf(false, 'https://example.com/ping');
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertNull($mock->getLastRequest());
    }

    public function testPingOnFailureIfPingsTheUrlWhenTheConditionIsTrue()
    {
        $mock = $this->swapHttpClientWithMock();

        $event = $this->newEvent();
        $event->exitCode = 1;

        $event->pingOnFailureIf(true, 'https://example.com/ping');
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertPinged($mock);
    }

    public function testPingOnFailureIfDoesNotPingTheUrlWhenTheConditionIsFalse()
    {
        $mock = $this->swapHttpClientWithMock();

        $event = $this->newEvent();
        $event->exitCode = 1;

        $event->pingOnFailureIf(false, 'https://example.com/ping');
        $event->callAfterCallbacks($this->app->make(Container::class));

        $this->assertNull($mock->getLastRequest());
    }

    protected function swapHttpClientWithMock()
    {
        $mock = new MockHandler([new Psr7Response(200)]);

        $this->swap(HttpClient::class, new HttpClient([
            'handler' => HandlerStack::create($mock),
        ]));

        return $mock;
    }

    protected function newEvent()
    {
        return new Event(m::mock(EventMutex::class), 'php -i');
    }

    protected function assertPinged(MockHandler $mock)
    {
        $this->assertNotNull($mock->getLastRequest());
        $this->assertSame('https://example.com/ping', (string) $mock->getLastRequest()->getUri());
    }
}
