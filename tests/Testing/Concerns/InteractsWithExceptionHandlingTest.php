<?php

namespace Illuminate\Tests\Testing\Concerns;

use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InteractsWithExceptionHandlingTest extends TestCase
{
    use InteractsWithExceptionHandling;

    public function test_assertThrown_returns_exception_when_Throwable()
    {
        $func = static fn(): never => throw new RuntimeException("My runtime exception");

        $thrownException = $this->assertThrown($func);

        $this->assertSame("My runtime exception", $thrownException->getMessage());
    }

    public function test_assertThrown_returns_exception_when_custom_exception()
    {
        $func = static function(): never {
            throw (new CustomExceptionForInteractsWithExceptionHandlingTest("A message"))->setValue(1993);
        };

        $thrownException1 = $this->assertThrown($func, RuntimeException::class);
        $thrownException2 = $this->assertThrown($func, CustomExceptionForInteractsWithExceptionHandlingTest::class);

        foreach([$thrownException1, $thrownException2] as $exception) {
            $this->assertSame("A message", $exception->getMessage());
            $this->assertSame(1993, $exception->value);
            $this->assertInstanceOf(CustomExceptionForInteractsWithExceptionHandlingTest::class, $exception);
        }
    }

    public function test_assertThrown_fails_if_not_thrown()
    {
        $func = static fn(): int => 200;

        try {
            $this->assertThrown($func);
            $this->fail("assertThrown did not raise an assertion error");
        } catch (AssertionFailedError $assertionFailedError) {
            $this->assertSame("Did not throw expected exception Throwable", $assertionFailedError->getMessage());
        }
    }

    public function test_assertThrown_fails_if_not_subclass()
    {
        $func = static fn(): never => throw new CustomExceptionForInteractsWithExceptionHandlingTest("invalid argument exception");

        try {
            $this->assertThrown($func, \InvalidArgumentException::class, "abcd %s");
            $this->fail("assertThrown did not raise an assertion error");
        } catch (AssertionFailedError $assertionFailedError) {
            $this->assertSame("abcd InvalidArgumentException", $assertionFailedError->getMessage());
        }
    }
}

class CustomExceptionForInteractsWithExceptionHandlingTest extends RuntimeException
{
    public $value;

    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }
}
