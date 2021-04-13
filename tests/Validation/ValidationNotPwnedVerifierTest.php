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
            $this->assertFalse($verifier->verify($password));
        }
    }

    public function testApiResponseGoesWrong()
    {
        $httpFactory = m::mock(HttpFactory::class);
        $response = m::mock(Response::class);

        $httpFactory = m::mock(HttpFactory::class);

        $httpFactory
            ->shouldReceive('withHeaders')
            ->with(['Add-Padding' => true])
            ->andThrow($httpFactory);

        $httpFactory->shouldReceive('get')
            ->andReturn($response);

        $response->shouldReceive('successful')
            ->andReturn(true);

        $response->shouldReceive('body')
            ->andReturn('');

        $verifier = new NotPwnedVerifier($httpFactory);

        $this->assertTrue($verifier->verify(123123123));
    }

    public function testApiGoesDown()
    {
        $httpFactory = m::mock(HttpFactory::class);
        $response = m::mock(Response::class);

        $httpFactory
            ->shouldReceive('withHeaders')
            ->with(['Add-Padding' => true])
            ->andThrow($httpFactory);

        $httpFactory->shouldReceive('get')
            ->andReturn($response);

        $response->shouldReceive('successful')
            ->andReturn(false);

        $verifier = new NotPwnedVerifier($httpFactory);

        $this->assertTrue($verifier->verify(123123123));
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
            ->with(['Add-Padding' => true])
            ->andThrow($httpFactory);

        $httpFactory
            ->shouldReceive('get')
            ->andThrow($exception);

        $verifier = new NotPwnedVerifier($httpFactory);
        $this->assertTrue($verifier->verify(123123123));
    }
}
