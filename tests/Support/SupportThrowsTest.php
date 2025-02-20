<?php

namespace Illuminate\Tests\Support;

use Exception;
use Illuminate\Support\Traits\Throws;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SupportThrowsTest extends TestCase
{
    public function testThrowsIfWithDefault()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwIf(false, 'test-foo'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test-foo');

        $throws->throwIf(true, 'test-foo');
    }

    public function testThrowsIfWithCallback()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwIf(fn () => false, 'test-foo'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test-foo');

        $throws->throwIf(fn () => true, 'test-foo');
    }

    public function testThrowsIfWithExceptionInstance()
    {
        $throws = ThrowsClass::make();
        $exception = new Exception('test-bar');

        $this->assertSame($throws, $throws->throwIf(false, $exception));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test-bar');

        $throws->throwIf(true, $exception);
    }

    public function testThrowsIfWithCallbackAsExceptionInstance()
    {
        $throws = ThrowsClass::make();
        $exception = new Exception('test-bar');

        $this->assertSame($throws, $throws->throwIf(false, fn () => $exception));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test-bar');

        $throws->throwIf(true, function ($instance) use ($throws, $exception) {
            $this->assertSame($throws, $instance);

            return $exception;
        });
    }

    public function testThrowsIfWithCallbackAsExceptionMessage()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwIf(false, fn () => 'test-foo'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test-foo');

        $throws->throwIf(true, fn () => 'test-foo');
    }

    public function testThrowsIfWithCallbackAsExceptionClassString()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwIf(false, fn () => Exception::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('');

        $throws->throwIf(true, fn () => Exception::class);
    }

    public function testThrowsIfWithCallbackAsExceptionClassStringWithArguments()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwIf(false, fn () => Exception::class), 'test-foo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test-foo');

        $throws->throwIf(true, fn () => Exception::class, 'test-foo');
    }

    public function testThrowsUnlessWithDefault()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwUnless(true, 'test-foo'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test-foo');

        $throws->throwUnless(false, 'test-foo');
    }

    public function testThrowsUnlessWithCallback()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwUnless(fn () => true, 'test-foo'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test-foo');

        $throws->throwUnless(fn () => false, 'test-foo');
    }

    public function testThrowsUnlessWithExceptionInstance()
    {
        $throws = ThrowsClass::make();
        $exception = new Exception('test-bar');

        $this->assertSame($throws, $throws->throwUnless(true, $exception));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test-bar');

        $throws->throwUnless(false, $exception);
    }

    public function testThrowsUnlessWithCallbackAsExceptionInstance()
    {
        $throws = ThrowsClass::make();
        $exception = new Exception('test-bar');

        $this->assertSame($throws, $throws->throwUnless(true, fn () => $exception));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test-bar');

        $throws->throwUnless(false, function ($instance) use ($throws, $exception) {
            $this->assertSame($throws, $instance);

            return $exception;
        });
    }

    public function testThrowsUnlessWithCallbackAsExceptionMessage()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwUnless(true, fn () => 'test-foo'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test-foo');

        $throws->throwUnless(false, fn () => 'test-foo');
    }

    public function testThrowsUnlessWithCallbackAsExceptionClassString()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwUnless(true, fn () => Exception::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('');

        $throws->throwUnless(false, fn () => Exception::class);
    }

    public function testThrowsUnlessWithCallbackAsExceptionClassStringWithArguments()
    {
        $throws = ThrowsClass::make();

        $this->assertSame($throws, $throws->throwUnless(true, fn () => Exception::class), 'test-foo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test-foo');

        $throws->throwUnless(false, fn () => Exception::class, 'test-foo');
    }
}

class ThrowsClass
{
    use Throws;

    public static function make()
    {
        return new static;
    }
}
