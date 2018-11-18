<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Orchestra\Testbench\TestCase;

class MakesHttpRequestsTest extends TestCase
{
    public function testFromSetsHeaderAndSession()
    {
        $this->from('previous/url');

        $this->assertSame('previous/url', $this->defaultHeaders['referer']);
        $this->assertSame('previous/url', $this->app['session']->previousUrl());
    }

    public function testWithoutAndWithMiddleware()
    {
        $this->assertFalse($this->app->has('middleware.disable'));

        $this->withoutMiddleware();
        $this->assertTrue($this->app->has('middleware.disable'));
        $this->assertTrue($this->app->make('middleware.disable'));

        $this->withMiddleware();
        $this->assertFalse($this->app->has('middleware.disable'));
    }

    public function testWithoutAndWithMiddlewareWithParameter()
    {
        $next = function ($request) {
            return $request;
        };

        $this->assertFalse($this->app->has(MyMiddleware::class));
        $this->assertEquals(
            'fooWithMiddleware',
            $this->app->make(MyMiddleware::class)->handle('foo', $next)
        );

        $this->withoutMiddleware(MyMiddleware::class);
        $this->assertTrue($this->app->has(MyMiddleware::class));
        $this->assertEquals(
            'foo',
            $this->app->make(MyMiddleware::class)->handle('foo', $next)
        );

        $this->withMiddleware(MyMiddleware::class);
        $this->assertFalse($this->app->has(MyMiddleware::class));
        $this->assertEquals(
            'fooWithMiddleware',
            $this->app->make(MyMiddleware::class)->handle('foo', $next)
        );
    }
}

class MyMiddleware
{
    public function handle($request, $next)
    {
        return $next($request.'WithMiddleware');
    }
}
