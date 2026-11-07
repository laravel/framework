<?php

namespace Illuminate\Tests\Validation;

use Generator;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Testing\Fakes\ExceptionHandlerFake;
use Illuminate\Validation\NotPwnedVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidationNotPwnedVerifierTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    #[DataProvider('dataProviderEmptyValues')]
    public function testEmptyValues($password): void
    {
        $http = new HttpFactory;
        $http->fake();
        $verifier = new NotPwnedVerifier($http);

        $this->assertFalse($verifier->verify([
            'value' => $password,
            'threshold' => 0,
        ]));

        $http->assertNothingSent();
    }

    public static function dataProviderEmptyValues(): Generator
    {
        yield 'empty string' => [''];
        yield 'false' => [false];
        yield 'zero' => [0];
    }

    public function testApiResponseGoesWrong()
    {
        $http = new HttpFactory;
        $http->fake(['api.pwnedpasswords.com/*' => HttpFactory::response('', 200)]);
        $verifier = new NotPwnedVerifier($http);

        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));

        $http->assertSentCount(1);
    }

    public function testApiGoesDown()
    {
        $http = new HttpFactory;
        $http->fake(['api.pwnedpasswords.com/*' => HttpFactory::response('', 500)]);
        $verifier = new NotPwnedVerifier($http);

        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));

        $http->assertSentCount(1);
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

        $http = new HttpFactory;
        $http->fake(['api.pwnedpasswords.com/*' => HttpFactory::response($differentSuffix.':5', 200)]);
        $verifier = new NotPwnedVerifier($http);

        $this->assertTrue($verifier->verify([
            'value' => $password,
            'threshold' => 0,
        ]));

        $http->assertSent(fn ($request) => $request->url() === 'https://api.pwnedpasswords.com/range/'.$hashPrefix
            && $request->hasHeader('Add-Padding'));
    }

    public function testDnsDown()
    {
        $container = Container::getInstance();

        $exceptionHandler = new ExceptionHandlerFake(new Handler($container));
        $container->bind(ExceptionHandler::class, function () use ($exceptionHandler) {
            return $exceptionHandler;
        });

        $http = new HttpFactory;
        $http->fake(HttpFactory::failedConnection());
        $verifier = new NotPwnedVerifier($http);

        $this->assertTrue($verifier->verify([
            'value' => 123123123,
            'threshold' => 0,
        ]));

        $exceptionHandler->assertReported(ConnectionException::class);

        unset($container[ExceptionHandler::class]);
    }
}
