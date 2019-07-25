<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * @group integration
 */
class FallbackRouteTest extends TestCase
{
    public function test_basic_fallback()
    {
        Route::fallback(function () {
            return response('fallback', 404);
        });

        Route::get('one', function () {
            return 'one';
        });

        $this->assertStringContainsString('one', $this->get('/one')->getContent());
        $this->assertStringContainsString('fallback', $this->get('/non-existing')->getContent());
        $this->assertEquals(404, $this->get('/non-existing')->getStatusCode());
    }

    public function test_fallback_with_prefix()
    {
        Route::group(['prefix' => 'prefix'], function () {
            Route::fallback(function () {
                return response('fallback', 404);
            });

            Route::get('one', function () {
                return 'one';
            });
        });

        $this->assertStringContainsString('one', $this->get('/prefix/one')->getContent());
        $this->assertStringContainsString('fallback', $this->get('/prefix/non-existing')->getContent());
        $this->assertStringContainsString('fallback', $this->get('/prefix/non-existing/with/multiple/segments')->getContent());
        $this->assertStringContainsString('Not Found', $this->get('/non-existing')->getContent());
    }

    public function test_fallback_with_wildcards()
    {
        Route::fallback(function () {
            return response('fallback', 404);
        });

        Route::get('one', function () {
            return 'one';
        });

        Route::get('{any}', function () {
            return 'wildcard';
        })->where('any', '.*');

        $this->assertStringContainsString('one', $this->get('/one')->getContent());
        $this->assertStringContainsString('wildcard', $this->get('/non-existing')->getContent());
        $this->assertEquals(200, $this->get('/non-existing')->getStatusCode());
    }

    public function test_no_routes()
    {
        Route::fallback(function () {
            return response('fallback', 404);
        });

        $this->assertStringContainsString('fallback', $this->get('/non-existing')->getContent());
        $this->assertEquals(404, $this->get('/non-existing')->getStatusCode());
    }

    public function test_respond_with_named_fallback_route()
    {
        Route::fallback(function () {
            return response('fallback', 404);
        })->name('testFallbackRoute');

        Route::get('one', function () {
            return Route::respondWithRoute('testFallbackRoute');
        });

        $this->assertStringContainsString('fallback', $this->get('/non-existing')->getContent());
        $this->assertStringContainsString('fallback', $this->get('/one')->getContent());
    }

    public function test_no_fallbacks()
    {
        Route::get('one', function () {
            return 'one';
        });

        $this->assertStringContainsString('one', $this->get('/one')->getContent());
        $this->assertEquals(200, $this->get('/one')->getStatusCode());
    }
}
