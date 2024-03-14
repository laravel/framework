<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Exception;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

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
            report(new Exception('Test exception'));
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
            report(new Exception('Test exception'));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->get('/');
    }
}
