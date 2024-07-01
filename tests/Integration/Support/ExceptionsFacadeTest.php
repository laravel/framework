<?php

namespace Illuminate\Tests\Integration\Support;

use ErrorException;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Testing\Fakes\ExceptionHandlerFake;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use RuntimeException;
use Throwable;

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
        Exceptions::assertReportedCount(2);
    }

    public function testFakeAssertReportedCount()
    {
        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));
        report(new RuntimeException('test 2'));

        Exceptions::assertReportedCount(2);
    }

    public function testFakeAssertReportedCountMayFail()
    {
        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));
        report(new RuntimeException('test 2'));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The total number of exceptions reported was 2 instead of 1.');

        Exceptions::assertReportedCount(1);
    }

    public function testFakeAssertReportedWithFakedExceptions()
    {
        Exceptions::fake([
            RuntimeException::class,
        ]);

        Exceptions::report(new RuntimeException('test 1'));
        report(new RuntimeException('test 2'));
        report(new InvalidArgumentException('test 3'));

        Exceptions::assertReported(RuntimeException::class);
        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'test 1');
        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'test 2');

        Exceptions::assertNotReported(InvalidArgumentException::class);
        Exceptions::assertReportedCount(2);
    }

    public function testFakeAssertReportedAsStringMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [InvalidArgumentException] exception was not reported.');

        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));

        Exceptions::assertReportedCount(1);
        Exceptions::assertReported(InvalidArgumentException::class);
    }

    public function testFakeAssertReportedAsClosureMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [InvalidArgumentException] exception was not reported.');

        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));

        Exceptions::assertReportedCount(1);
        Exceptions::assertReported(fn (InvalidArgumentException $e) => $e->getMessage() === 'test 2');
    }

    public function testFakeAssertReportedWithFakedExceptionsMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [RuntimeException] exception was not reported.');

        Exceptions::fake(InvalidArgumentException::class);

        Exceptions::report(new InvalidArgumentException('test 1'));
        report(new RuntimeException('test 2'));

        Exceptions::assertReported(InvalidArgumentException::class);
        Exceptions::assertReported(RuntimeException::class);
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

        Exceptions::assertReportedCount(2);
    }

    public function testFakeAssertNotReportedWithFakedExceptions()
    {
        Exceptions::fake([
            InvalidArgumentException::class,
        ]);

        report(new RuntimeException('test 2'));

        Exceptions::assertNotReported(InvalidArgumentException::class);
        Exceptions::assertNotReported(RuntimeException::class);
    }

    public function testFakeAssertNotReportedMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [RuntimeException] exception was reported.');

        Exceptions::fake();

        Exceptions::report(new RuntimeException('test 1'));

        Exceptions::assertNotReported(RuntimeException::class);
    }

    public function testFakeAssertNotReportedAsClosureMayFail()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [RuntimeException] exception was reported.');

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

    public function testFakeAssertNothingReportedWithFakedExceptions()
    {
        Exceptions::fake([
            InvalidArgumentException::class,
        ]);

        report(new RuntimeException('test 1'));

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

    public function testReportedExceptionsAreNotThrownByDefault()
    {
        report(new Exception('Test exception'));

        $this->assertTrue(true);
    }

    public function testReportedExceptionsAreNotThrownByDefaultWithExceptionHandling()
    {
        Route::get('/', function () {
            report(new Exception('Test exception'));
        });

        $this->get('/')->assertStatus(200);
    }

    public function testReportedExceptionsAreNotThrownByDefaultWithoutExceptionHandling()
    {
        $this->withoutExceptionHandling();

        Route::get('/', function () {
            report(new Exception('Test exception'));
        });

        $this->get('/')->assertStatus(200);
    }

    public function testThrowOnReport()
    {
        Exceptions::fake()->throwOnReport();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        report(new Exception('Test exception'));
    }

    public function testThrowOnReportDoesNotThrowExceptionsThatShouldNotBeReported()
    {
        Exceptions::fake()->throwOnReport();

        Route::get('/302', function () {
            Validator::validate(['name' => ''], ['name' => 'required']);
        });

        $this->get('/302')->assertStatus(302);

        Route::get('/404', function () {
            throw new ModelNotFoundException();
        });

        $this->get('/404')->assertStatus(404);

        $this->doesNotPerformAssertions();

        Exceptions::assertReportedCount(0);
    }

    public function testThrowOnReportWithExceptionHandling()
    {
        Exceptions::fake()->throwOnReport();

        Route::get('/', function () {
            report(new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }

    public function testThrowOnReportWithoutExceptionHandling()
    {
        Exceptions::fake()->throwOnReport();

        $this->withoutExceptionHandling();

        Route::get('/', function () {
            report(new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }

    public function testThrowOnReportRegardlessOfTheCallingOrderOfWithoutExceptionHandling()
    {
        Exceptions::fake()->throwOnReport();

        $this
            ->withoutExceptionHandling()
            ->withExceptionHandling()
            ->withoutExceptionHandling();

        Route::get('/', function () {
            rescue(fn () => throw new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }

    public function testThrowOnReportRegardlessOfTheCallingOrderOfWithExceptionHandling()
    {
        Exceptions::fake()->throwOnReport();

        $this->withoutExceptionHandling()
            ->withExceptionHandling()
            ->withoutExceptionHandling()
            ->withExceptionHandling();

        Route::get('/', function () {
            rescue(fn () => throw new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }

    public function testThrowOnReportWithFakedExceptions()
    {
        Exceptions::fake([InvalidArgumentException::class])->throwOnReport();

        $this->expectException(InvalidArgumentException::class);

        report(new Exception('Test exception'));
        report(new RuntimeException('Test exception'));
        report(new InvalidArgumentException('Test exception'));
    }

    public function testThrowOnReportWithFakedExceptionsFromFacade()
    {
        Exceptions::fake([InvalidArgumentException::class])->throwOnReport();

        $this->expectException(InvalidArgumentException::class);

        report(new Exception('Test exception'));
        report(new RuntimeException('Test exception'));
        Exceptions::assertReportedCount(0);

        report(new InvalidArgumentException('Test exception'));
    }

    public function testThrowOnReporEvenWhenAppReportablesReturnFalse()
    {
        app(ExceptionHandler::class)->reportable(function (Throwable $e) {
            return false;
        });

        Exceptions::fake()->throwOnReport();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        report(new Exception('Test exception'));
    }

    public function testAppReportablesAreNotCalledIfExceptionIsNotFaked()
    {
        app(ExceptionHandler::class)->reportable(function (Throwable $e) {
            throw new InvalidArgumentException($e->getMessage());
        });

        Exceptions::fake([RuntimeException::class, Exception::class]);

        report(new Exception('My exception message'));

        Exceptions::assertReported(Exception::class);
    }

    public function testThrowOnReportLeaveAppReportablesUntouched()
    {
        app(ExceptionHandler::class)->reportable(function (Throwable $e) {
            throw new InvalidArgumentException($e->getMessage());
        });

        Exceptions::fake([RuntimeException::class])->throwOnReport();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('My exception message');

        report(new Exception('My exception message'));
    }

    public function testThrowReportedExceptions()
    {
        Exceptions::fake();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        report(new Exception('Test exception'));

        Exceptions::throwFirstReported();
    }

    public function testThrowReportedExceptionsWithFakedExceptions()
    {
        Exceptions::fake([InvalidArgumentException::class]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Test exception');

        report(new RuntimeException('Test exception'));
        report(new InvalidArgumentException('Test exception'));

        Exceptions::throwFirstReported();
    }

    public function testThrowReportedExceptionsWhenThereIsNone()
    {
        Exceptions::fake();

        Exceptions::throwFirstReported();

        Exceptions::fake([InvalidArgumentException::class]);

        report(new RuntimeException('Test exception'));

        Exceptions::throwFirstReported();

        $this->doesNotPerformAssertions();
    }

    public function testFakingExceptionsThatShouldNotBeReportedWithExceptionHandling()
    {
        Exceptions::fake();

        Route::get('/302', function () {
            Validator::validate(['name' => ''], ['name' => 'required']);
        });

        $this->get('/302')->assertStatus(302);

        Route::get('/404', function () {
            throw new ModelNotFoundException();
        });

        $this->get('/404')->assertStatus(404);

        report(new ModelNotFoundException());

        Exceptions::assertNothingReported();
    }

    public function testFakingExceptionsThatShouldNotBeReportedWithRescueAndWithoutExceptionHandling()
    {
        Exceptions::fake();

        $this->withoutExceptionHandling();

        Route::get('/validation', function () {
            rescue(fn () => Validator::validate(['name' => ''], ['name' => 'required']));
        });

        $this->get('/validation')->assertStatus(200);

        Route::get('/model', function () {
            rescue(fn () => throw new ModelNotFoundException());
        });

        $this->get('/model')->assertStatus(200);

        rescue(fn () => throw new ModelNotFoundException());

        Exceptions::assertReportedCount(3);
    }

    public function testRescue()
    {
        Exceptions::fake();

        rescue(fn () => throw new Exception('Test exception'));

        Exceptions::assertReported(Exception::class);
    }

    public function testRescueWithoutReport()
    {
        Exceptions::fake();

        rescue(fn () => throw new Exception('Test exception'), null, false);

        Exceptions::assertNothingReported();
    }

    public function testFlowBetweenFakeAndTestExceptionHandling()
    {
        $this->assertInstanceOf(Handler::class, app(ExceptionHandler::class));

        Exceptions::fake();
        $this->assertInstanceOf(ExceptionHandlerFake::class, app(ExceptionHandler::class));
        $this->assertInstanceOf(Handler::class, Exceptions::fake()->handler());
        $this->assertFalse((new \ReflectionClass(Exceptions::fake()->handler()))->isAnonymous());

        Exceptions::fake();
        $this->assertInstanceOf(ExceptionHandlerFake::class, app(ExceptionHandler::class));
        $this->assertInstanceOf(Handler::class, Exceptions::fake()->handler());
        $this->assertFalse((new \ReflectionClass(Exceptions::fake()->handler()))->isAnonymous());

        $this->withoutExceptionHandling();
        $this->assertInstanceOf(ExceptionHandlerFake::class, app(ExceptionHandler::class));
        $this->assertInstanceOf(ExceptionHandler::class, Exceptions::fake()->handler());
        $this->assertTrue((new \ReflectionClass(Exceptions::fake()->handler()))->isAnonymous());

        $this->withExceptionHandling();
        $this->assertInstanceOf(ExceptionHandlerFake::class, app(ExceptionHandler::class));
        $this->assertInstanceOf(ExceptionHandler::class, Exceptions::fake()->handler());
        $this->assertFalse((new \ReflectionClass(Exceptions::fake()->handler()))->isAnonymous());

        Exceptions::fake();
        $this->assertInstanceOf(ExceptionHandlerFake::class, app(ExceptionHandler::class));
        $this->assertInstanceOf(Handler::class, Exceptions::fake()->handler());
        $this->assertFalse((new \ReflectionClass(Exceptions::fake()->handler()))->isAnonymous());
    }

    public function testFlowBetweenTestExceptionHandlingAndFake()
    {
        $this->withoutExceptionHandling();
        $this->assertTrue((new \ReflectionClass(app(ExceptionHandler::class)))->isAnonymous());

        Exceptions::fake();
        $this->assertInstanceOf(ExceptionHandlerFake::class, app(ExceptionHandler::class));
        $this->assertInstanceOf(ExceptionHandler::class, Exceptions::fake()->handler());
        $this->assertTrue((new \ReflectionClass(Exceptions::fake()->handler()))->isAnonymous());

        Exceptions::fake();
        $this->assertInstanceOf(ExceptionHandlerFake::class, app(ExceptionHandler::class));
        $this->assertInstanceOf(ExceptionHandler::class, Exceptions::fake()->handler());
        $this->assertTrue((new \ReflectionClass(Exceptions::fake()->handler()))->isAnonymous());

        $this->withExceptionHandling();
        $this->assertInstanceOf(ExceptionHandlerFake::class, app(ExceptionHandler::class));
        $this->assertInstanceOf(Handler::class, Exceptions::fake()->handler());
        $this->assertFalse((new \ReflectionClass(Exceptions::fake()->handler()))->isAnonymous());
    }

    public function testWithDeprecationHandling()
    {
        Exceptions::fake();

        Route::get('/', function () {
            str_contains(null, null);
        });

        $this->get('/')->assertStatus(200);

        Exceptions::assertNothingReported();
    }

    public function testWithoutDeprecationHandler()
    {
        Exceptions::fake();

        $this->withoutDeprecationHandling();

        Route::get('/', function () {
            str_contains(null, null);
        });

        $this->get('/')->assertStatus(500);

        Exceptions::assertReported(function (ErrorException $e) {
            return $e->getMessage() === 'str_contains(): Passing null to parameter #1 ($haystack) of type string is deprecated';
        });

        Exceptions::assertReportedCount(1);
    }
}
