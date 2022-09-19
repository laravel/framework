<?php

namespace Illuminate\Tests\Integration\Http;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class RequestDurationThresholdTest extends TestCase
{
    public function testItCanHandleExceedingRequestDuration()
    {
        Route::get('test-route', fn () => 'ok');
        $request = Request::create('http://localhost/test-route');
        $response = new Response();
        $called = false;
        $kernel = $this->app[Kernel::class];
        $kernel->whenRequestLifecycleIsLongerThan(CarbonInterval::seconds(1), function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(now());
        $kernel->handle($request);

        Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMilliseconds(1));
        $kernel->terminate($request, $response);

        $this->assertTrue($called);
    }

    public function testItDoesntCallWhenExactlyThresholdDuration()
    {
        Route::get('test-route', fn () => 'ok');
        $request = Request::create('http://localhost/test-route');
        $response = new Response();
        $called = false;
        $kernel = $this->app[Kernel::class];
        $kernel->whenRequestLifecycleIsLongerThan(CarbonInterval::seconds(1), function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(now());
        $kernel->handle($request);

        Carbon::setTestNow(Carbon::now()->addSeconds(1));
        $kernel->terminate($request, $response);

        $this->assertFalse($called);
    }

    public function testItProvidesRequestToHandler()
    {
        Route::get('test-route', fn () => 'ok');
        $request = Request::create('http://localhost/test-route');
        $response = new Response();
        $url = null;
        $kernel = $this->app[Kernel::class];
        $kernel->whenRequestLifecycleIsLongerThan(CarbonInterval::seconds(1), function ($startedAt, $request) use (&$url) {
            $url = $request->url();
        });

        Carbon::setTestNow(now());
        $kernel->handle($request);

        Carbon::setTestNow(Carbon::now()->addSeconds(2));
        $kernel->terminate($request, $response);

        $this->assertSame('http://localhost/test-route', $url);
    }

    public function testItCanExceedThresholdWhenSpecifyingDurationAsMilliseconds()
    {
        Route::get('test-route', fn () => 'ok');
        $request = Request::create('http://localhost/test-route');
        $response = new Response();
        $called = false;
        $kernel = $this->app[Kernel::class];
        $kernel->whenRequestLifecycleIsLongerThan(1000, function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(now());
        $kernel->handle($request);

        Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMilliseconds(1));
        $kernel->terminate($request, $response);

        $this->assertTrue($called);
    }

    public function testItCanStayUnderThresholdWhenSpecifyingDurationAsMilliseconds()
    {
        Route::get('test-route', fn () => 'ok');
        $request = Request::create('http://localhost/test-route');
        $response = new Response();
        $called = false;
        $kernel = $this->app[Kernel::class];
        $kernel->whenRequestLifecycleIsLongerThan(1000, function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(now());
        $kernel->handle($request);

        Carbon::setTestNow(Carbon::now()->addSeconds(1));
        $kernel->terminate($request, $response);

        $this->assertFalse($called);
    }

    public function testItCanExceedThresholdWhenSpecifyingDurationAsDateTime()
    {
        Route::get('test-route', fn () => 'ok');
        $request = Request::create('http://localhost/test-route');
        $response = new Response();
        $called = false;
        $kernel = $this->app[Kernel::class];
        $kernel->whenRequestLifecycleIsLongerThan(now()->addSeconds(1), function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(now());
        $kernel->handle($request);

        Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMilliseconds(1));
        $kernel->terminate($request, $response);

        $this->assertTrue($called);
    }

    public function testItCanStayUnderThresholdWhenSpecifyingDurationAsDateTime()
    {
        Route::get('test-route', fn () => 'ok');
        $request = Request::create('http://localhost/test-route');
        $response = new Response();
        $called = false;
        $kernel = $this->app[Kernel::class];
        $kernel->whenRequestLifecycleIsLongerThan(now()->addSeconds(1), function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(now());
        $kernel->handle($request);

        Carbon::setTestNow(Carbon::now()->addSeconds(1));
        $kernel->terminate($request, $response);

        $this->assertFalse($called);
    }

    public function testItClearsStartTimeAfterHandlingRequest()
    {
        $kernel = $this->app[Kernel::class];
        Route::get('test-route', fn () => 'ok');
        $request = Request::create('http://localhost/test-route');
        $response = new Response();

        Carbon::setTestNow(now());
        $kernel->handle($request);
        $this->assertTrue(Carbon::now()->eq($kernel->requestStartedAt()));

        $kernel->terminate($request, $response);
        $this->assertNull($kernel->requestStartedAt());
    }
}
