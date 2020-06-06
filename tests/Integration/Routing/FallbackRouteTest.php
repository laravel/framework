<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class FallbackRouteTest extends TestCase
{
    public function testBasicFallback()
    {
        Route::fallback(static function () {
            return response('fallback', 404);
        });

        Route::get('one', static function () {
            return 'one';
        });

        $this->assertStringContainsString('one', $this->get('/one')->getContent());
        $this->assertStringContainsString('fallback', $this->get('/non-existing')->getContent());
        $this->assertEquals(404, $this->get('/non-existing')->getStatusCode());
    }

    public function testFallbackWithPrefix()
    {
        Route::group(['prefix' => 'prefix'], static function () {
            Route::fallback(static function () {
                return response('fallback', 404);
            });

            Route::get('one', static function () {
                return 'one';
            });
        });

        $this->assertStringContainsString('one', $this->get('/prefix/one')->getContent());
        $this->assertStringContainsString('fallback', $this->get('/prefix/non-existing')->getContent());
        $this->assertStringContainsString('fallback', $this->get('/prefix/non-existing/with/multiple/segments')->getContent());
        $this->assertStringContainsString('Not Found', $this->get('/non-existing')->getContent());
    }

    public function testFallbackWithWildcards()
    {
        Route::fallback(static function () {
            return response('fallback', 404);
        });

        Route::get('one', static function () {
            return 'one';
        });

        Route::get('{any}', static function () {
            return 'wildcard';
        })->where('any', '.*');

        $this->assertStringContainsString('one', $this->get('/one')->getContent());
        $this->assertStringContainsString('wildcard', $this->get('/non-existing')->getContent());
        $this->assertEquals(200, $this->get('/non-existing')->getStatusCode());
    }

    public function testNoRoutes()
    {
        Route::fallback(static function () {
            return response('fallback', 404);
        });

        $this->assertStringContainsString('fallback', $this->get('/non-existing')->getContent());
        $this->assertEquals(404, $this->get('/non-existing')->getStatusCode());
    }

    public function testRespondWithNamedFallbackRoute()
    {
        Route::fallback(static function () {
            return response('fallback', 404);
        })->name('testFallbackRoute');

        Route::get('one', static function () {
            return Route::respondWithRoute('testFallbackRoute');
        });

        $this->assertStringContainsString('fallback', $this->get('/non-existing')->getContent());
        $this->assertStringContainsString('fallback', $this->get('/one')->getContent());
    }

    public function testNoFallbacks()
    {
        Route::get('one', static function () {
            return 'one';
        });

        $this->assertStringContainsString('one', $this->get('/one')->getContent());
        $this->assertEquals(200, $this->get('/one')->getStatusCode());
    }
}
