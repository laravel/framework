<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\DecryptInputValues;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class DecryptInputValuesTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container;
        $this->container->singleton(EncrypterContract::class, function () {
            return new Encrypter(str_repeat('a', 16));
        });
    }

    public function testDecryptsEncryptedValue()
    {
        $encrypter = $this->container->make(EncrypterContract::class);
        $message = 'bar';

        $this->container->make(DecryptInputValues::class)->handle(
            Request::create('/test', 'GET', ['foo' => $encrypter->encrypt($message, false)]),
            function (Request $request) use ($message) {
                $this->assertSame($message, $request->get('foo'));
            });
    }

    public function testConvertsImproperlyDecryptedValueToNull()
    {
        $this->container->make(DecryptInputValues::class)->handle(
            Request::create('/test', 'GET', ['foo' => 'bar']),
            function (Request $request) {
                $this->assertNull($request->get('foo'));
            });
    }
}
