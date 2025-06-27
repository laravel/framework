<?php

namespace Illuminate\Tests\Testing\Concerns;

use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use InvalidArgumentException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InteractsWithExceptionHandlingTest extends TestCase
{
    use InteractsWithExceptionHandling;

    public function test_assert_thrown_returns_exception_when_throwable()
    {
        $func = static fn (): never => throw new RuntimeException('My runtime exception');

        $thrownException = $this->assertThrown($func);

        $this->assertSame('My runtime exception', $thrownException->getMessage());
    }

    public function test_assert_thrown_returns_exception_when_custom_exception()
    {
        $func = static function (): never {
            throw (new CustomExceptionForInteractsWithExceptionHandlingTest('A message'))->setValue(1993);
        };

        $thrownException1 = $this->assertThrown($func, RuntimeException::class);
        $thrownException2 = $this->assertThrown($func, CustomExceptionForInteractsWithExceptionHandlingTest::class);

        foreach ([$thrownException1, $thrownException2] as $exception) {
            $this->assertSame('A message', $exception->getMessage());
            $this->assertSame(1993, $exception->value);
            $this->assertInstanceOf(CustomExceptionForInteractsWithExceptionHandlingTest::class, $exception);
        }
    }

    public function test_assert_thrown_fails_if_not_thrown()
    {
        $func = static fn (): int => 200;

        try {
            $this->assertThrown($func);
            $this->fail('assertThrown did not raise an assertion error');
        } catch (AssertionFailedError $assertionFailedError) {
            $this->assertSame('Did not throw expected exception', $assertionFailedError->getMessage());
        }
    }

    public function test_assert_thrown_fails_if_not_subclass()
    {
        $func = static fn (): never => throw new CustomExceptionForInteractsWithExceptionHandlingTest('invalid argument exception');

        try {
            $this->assertThrown($func, InvalidArgumentException::class, 'abcd');
            $this->fail('assertThrown did not raise an assertion error');
        } catch (AssertionFailedError $assertionFailedError) {
            $this->assertStringContainsString(
                'Expected to throw InvalidArgumentException but threw Illuminate\Tests\Testing\Concerns\CustomExceptionForInteractsWithExceptionHandlingTest',
                $assertionFailedError->getMessage()
            );
        }
    }

    public function test_assert_thrown_matches_expected_exception_test_passes()
    {
        $func = static fn (): never => throw new CustomExceptionForInteractsWithExceptionHandlingTest('zzz');

        $this->assertThrown($func, CustomExceptionForInteractsWithExceptionHandlingTest::class);
        $this->assertThrown($func, RuntimeException::class);
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
