<?php

namespace Illuminate\Tests\Validation;

use Generator;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Validation\NotPwnedVerifier;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidationNotPwnedVerifierTest extends TestCase
{
    #[DataProvider('dataProviderEmptyValues')]
    public function testEmptyValues($password): void
    {
        $httpFactory = Mockery::mock(HttpFactory::class);
        $verifier = new NotPwnedVerifier($httpFactory);

        $this->assertFalse($verifier->verify([
            'value' => $password,
            'threshold' => 0,
        ]));
    }

    public static function dataProviderEmptyValues(): Generator
    {
        yield 'empty string' => [''];
        yield 'false' => [false];
        yield 'zero' => [0];
    }

    public function testApiResponseGoesWrong()
    {
        $httpFactory = Mockery::mock(HttpFactory::class);
        $response = Mockery::mock(Response::class);

        $httpFactory = Mockery::mock(HttpFactory::class);

        $httpFactory
            ->expects('withHeaders')
            ->with(['Add-Padding' => true])
            ->andReturn($httpFactory);

        $httpFactory
            ->expects('timeout')
            ->with(30)
            ->andReturn($httpFactory);

        $httpFactory->expects('get')
            ->andReturn($response);

        $response->expects('successful')
            ->andReturn(true);

        $response->expects('body')
            ->andReturn('');

        $verifier = new NotPwnedVerifier($httpFactory);

        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));
    }

    public function testApiGoesDown()
    {
        $httpFactory = Mockery::mock(HttpFactory::class);
        $response = Mockery::mock(Response::class);

        $httpFactory
            ->expects('withHeaders')
            ->with(['Add-Padding' => true])
            ->andReturn($httpFactory);

        $httpFactory
            ->expects('timeout')
            ->with(30)
            ->andReturn($httpFactory);

        $httpFactory->expects('get')
            ->andReturn($response);

        $response->expects('successful')
            ->andReturn(false);

        $verifier = new NotPwnedVerifier($httpFactory);

        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));
    }

    public function testMagicHashDoesNotCauseFalsePositive()
    {
        // "aaroZmOk" produces a SHA-1 hash that is all digits prefixed with "0E",
        // which PHP treats as scientific notation (zero) during loose comparison,
        // causing any other all-digit "0E" hash to falsely match.
        $password = 'aaroZmOk';
        $hash = strtoupper(sha1($password));
        $hashPrefix = substr($hash, 0, 5);

        $differentSuffix = '00000000000000000000000000000000000';

        $httpFactory = Mockery::mock(HttpFactory::class);
        $response = Mockery::mock(Response::class);

        $httpFactory
            ->expects('withHeaders')
            ->with(['Add-Padding' => true])
            ->andReturn($httpFactory);

        $httpFactory
            ->expects('timeout')
            ->with(30)
            ->andReturn($httpFactory);

        $httpFactory->expects('get')
            ->with('https://api.pwnedpasswords.com/range/'.$hashPrefix)
            ->andReturn($response);

        $response->expects('successful')
            ->andReturn(true);

        $response->expects('body')
            ->andReturn($differentSuffix.':5');

        $verifier = new NotPwnedVerifier($httpFactory);

        $this->assertTrue($verifier->verify([
            'value' => $password,
            'threshold' => 0,
        ]));
    }

    public function testDnsDown()
    {
        $container = Container::getInstance();
        $exception = new ConnectionException();

        $exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $exceptionHandler->expects('report')->with($exception);
        $container->bind(ExceptionHandler::class, function () use ($exceptionHandler) {
            return $exceptionHandler;
        });

        $httpFactory = Mockery::mock(HttpFactory::class);

        $httpFactory
            ->expects('withHeaders')
            ->with(['Add-Padding' => true])
            ->andReturn($httpFactory);

        $httpFactory
            ->expects('timeout')
            ->with(30)
            ->andReturn($httpFactory);

        $httpFactory
            ->expects('get')
            ->andThrow($exception);

        $verifier = new NotPwnedVerifier($httpFactory);
        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));

        unset($container[ExceptionHandler::class]);
    }
}
