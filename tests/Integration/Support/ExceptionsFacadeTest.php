<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Testing\Fakes\ExceptionHandlerFake;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use RuntimeException;

class ExceptionsFacadeTest extends TestCase
{
    public function testFakeAssertReported()
    {
        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));
        report(new RuntimeException('test 2'));

        Exceptions::assertReported(RuntimeException::class);
        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'test 1');
        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'test 2');
    }

    public function testFakeAssertReportedAsStringMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [InvalidArgumentException] exception was not reported.');

        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));

        Exceptions::assertReported(InvalidArgumentException::class);
    }

    public function testFakeAssertReportedAsClosureMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [InvalidArgumentException] exception was not reported.');

        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));

        Exceptions::assertReported(fn (InvalidArgumentException $e) => $e->getMessage() === 'test 2');
    }

    public function testFakeAssertNotReported()
    {
        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));
        report(new RuntimeException('test 2'));

        Exceptions::assertNotReported(InvalidArgumentException::class);
        Exceptions::assertNotReported(fn (InvalidArgumentException $e) => $e->getMessage() === 'test 1');
        Exceptions::assertNotReported(fn (InvalidArgumentException $e) => $e->getMessage() === 'test 2');
        Exceptions::assertNotReported(fn (InvalidArgumentException $e) => $e->getMessage() === 'test 3');
        Exceptions::assertNotReported(fn (InvalidArgumentException $e) => $e->getMessage() === 'test 4');
    }

    public function testFakeAssertNotReportedMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [RuntimeException] exception was not reported.');

        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));

        Exceptions::assertNotReported(RuntimeException::class);
    }

    public function testFakeAssertNotReportedAsClosureMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [RuntimeException] exception was not reported.');

        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));

        Exceptions::assertNotReported(fn (RuntimeException $e) => $e->getMessage() === 'test 1');
    }

    public function testResolvesExceptionHandler()
    {
        $this->assertInstanceOf(
            ExceptionHandler::class,
            Exceptions::getFacadeRoot()
        );
    }

    public function testFakeAssertNothingReported()
    {
        Exceptions::fake();

        Exceptions::assertNothingReported();
    }

    public function testFakeAssertNothingReportedMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The following exceptions were reported: RuntimeException, RuntimeException, InvalidArgumentException.');

        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));
        report(new RuntimeException('test 2'));
        report(new InvalidArgumentException('test 3'));

        Exceptions::assertNothingReported();
    }

    public function testFakeMethodReturnsExceptionHandlerFake()
    {
        $this->assertInstanceOf(ExceptionHandlerFake::class, $fake = Exceptions::fake());
        $this->assertInstanceOf(ExceptionHandlerFake::class, Exceptions::getFacadeRoot());
        $this->assertInstanceOf(Handler::class, $fake->handler());

        $this->assertInstanceOf(ExceptionHandlerFake::class, $fake = Exceptions::fake());
        $this->assertInstanceOf(ExceptionHandlerFake::class, Exceptions::getFacadeRoot());
        $this->assertInstanceOf(Handler::class, $fake->handler());
    }

    public function testResolvesExceptionHandlerAsSingleton()
    {
        $this->assertSame(
            Exceptions::getFacadeRoot(),
            Exceptions::getFacadeRoot()
        );
    }
}
