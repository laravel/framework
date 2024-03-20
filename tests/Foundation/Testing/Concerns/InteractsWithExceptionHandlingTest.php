<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Throwable;

class InteractsWithExceptionHandlingTest extends TestCase
{
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

    public function testReportedExceptionsAreThrownWhenRequested()
    {
        $this->throwReportedExceptions();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        report(new Exception('Test exception'));
    }

    public function testReportedExceptionsAreThrownWhenRequestedWithExceptionHandling()
    {
        $this->throwReportedExceptions();

        Route::get('/', function () {
            report(new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }

    public function testReportedExceptionsAreThrownWhenRequestedWithoutExceptionHandling()
    {
        $this->withoutExceptionHandling()->throwReportedExceptions();

        Route::get('/', function () {
            report(new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }

    public function testReportedExceptionsAreThrownRegardlessOfTheCallingOrderOfWithoutExceptionHandling()
    {
        $this->throwReportedExceptions()
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

    public function testReportedExceptionsAreThrownRegardlessOfTheCallingOrderOfWithExceptionHandling()
    {
        $this->throwReportedExceptions()
            ->withoutExceptionHandling()
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

    public function testOnlySpecifiedExceptionsAreThrown()
    {
        $this->throwReportedExceptions([InvalidArgumentException::class]);

        $this->expectException(InvalidArgumentException::class);

        report(new Exception('Test exception'));
        report(new RuntimeException('Test exception'));
        report(new InvalidArgumentException('Test exception'));
    }

    public function testOnlySpecifiedExceptionsAreThrownWithExceptionHandling()
    {
        $this->throwReportedExceptions([InvalidArgumentException::class]);

        Route::get('/', function () {
            report(new Exception('Test exception'));
            report(new RuntimeException('Test exception'));
            report(new InvalidArgumentException('Test exception'));
        });

        $this->expectException(InvalidArgumentException::class);

        $this->get('/');
    }

    public function testOnlySpecifiedExceptionsAreThrownWithoutExceptionHandling()
    {
        $this->withoutExceptionHandling()->throwReportedExceptions([InvalidArgumentException::class]);

        Route::get('/', function () {
            report(new Exception('Test exception'));
            report(new RuntimeException('Test exception'));
            report(new InvalidArgumentException('Test exception'));
        });

        $this->expectException(InvalidArgumentException::class);

        $this->get('/');
    }

    public function testReportedExceptionsAreThrowEvenWhenAppReportablesReturnFalse()
    {
        app(ExceptionHandler::class)->reportable(function (Throwable $e) {
            return false;
        });

        $this->throwReportedExceptions();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        report(new Exception('Test exception'));
    }

    public function testReportedExceptionsAreThrowEvenWhenAppReportablesReturnFalseWithExceptionHandling()
    {
        app(ExceptionHandler::class)->reportable(function (Throwable $e) {
            return false;
        });

        $this->throwReportedExceptions();

        Route::get('/', function () {
            report(new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }

    public function testReportedExceptionsAreThrowEvenWhenAppReportablesReturnFalseWithoutExceptionHandling()
    {
        app(ExceptionHandler::class)->reportable(function (Throwable $e) {
            return false;
        });

        $this->withoutExceptionHandling()->throwReportedExceptions();

        Route::get('/', function () {
            report(new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }

    public function testAppReportablesAreLeftUntouched()
    {
        app(ExceptionHandler::class)->reportable(function (Throwable $e) {
            throw new InvalidArgumentException($e->getMessage());
        });

        $this->throwReportedExceptions([RuntimeException::class]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('My exception message');

        report(new Exception('My exception message'));
    }
}
