<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Validation\NotPwnedVerifier;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ValidationNotPwnedVerifierTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
    }

    public function testEmptyValues()
    {
        $httpFactory = m::mock(HttpFactory::class);
        $verifier = new NotPwnedVerifier($httpFactory);

        foreach (['', false, 0] as $password) {
            $this->assertFalse($verifier->verify([
                'value' => $password,
                'threshold' => 0,
            ]));
        }
    }

    public function testApiResponseGoesWrong()
    {
        $httpFactory = m::mock(HttpFactory::class);
        $response = m::mock(Response::class);

        $httpFactory = m::mock(HttpFactory::class);

        $httpFactory
            ->shouldReceive('withHeaders')
            ->once()
            ->with(['Add-Padding' => true])
            ->andReturn($httpFactory);

        $httpFactory
            ->shouldReceive('timeout')
            ->once()
            ->with(30)
            ->andReturn($httpFactory);

        $httpFactory->shouldReceive('get')
            ->once()
            ->andReturn($response);

        $response->shouldReceive('successful')
            ->once()
            ->andReturn(true);

        $response->shouldReceive('body')
            ->once()
            ->andReturn('');

        $verifier = new NotPwnedVerifier($httpFactory);

        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));
    }

    public function testApiGoesDown()
    {
        $httpFactory = m::mock(HttpFactory::class);
        $response = m::mock(Response::class);

        $httpFactory
            ->shouldReceive('withHeaders')
            ->once()
            ->with(['Add-Padding' => true])
            ->andReturn($httpFactory);

        $httpFactory
            ->shouldReceive('timeout')
            ->once()
            ->with(30)
            ->andReturn($httpFactory);

        $httpFactory->shouldReceive('get')
            ->once()
            ->andReturn($response);

        $response->shouldReceive('successful')
            ->once()
            ->andReturn(false);

        $verifier = new NotPwnedVerifier($httpFactory);

        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));
    }

    public function testDnsDown()
    {
        $container = Container::getInstance();
        $exception = new ConnectionException();

        $exceptionHandler = m::mock(ExceptionHandler::class);
        $exceptionHandler->shouldReceive('report')->once()->with($exception);
        $container->bind(ExceptionHandler::class, function () use ($exceptionHandler) {
            return $exceptionHandler;
        });

        $httpFactory = m::mock(HttpFactory::class);

        $httpFactory
            ->shouldReceive('withHeaders')
            ->once()
            ->with(['Add-Padding' => true])
            ->andReturn($httpFactory);

        $httpFactory
            ->shouldReceive('timeout')
            ->once()
            ->with(30)
            ->andReturn($httpFactory);

        $httpFactory
            ->shouldReceive('get')
            ->once()
            ->andThrow($exception);

        $verifier = new NotPwnedVerifier($httpFactory);
        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));

        unset($container[ExceptionHandler::class]);
    }
}
