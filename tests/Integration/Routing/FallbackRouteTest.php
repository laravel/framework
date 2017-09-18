<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\View;
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

        Route::get('one', function(){
            return 'one';
        });

        $this->assertContains('one', $this->get('/one')->getContent());
        $this->assertContains('fallback', $this->get('/non-existing')->getContent());
        $this->assertEquals(404, $this->get('/non-existing')->getStatusCode());
    }

    public function test_fallback_with_prefix()
    {
        Route::group(['prefix' => 'prefix'], function(){
            Route::fallback(function () {
                return response('fallback', 404);
            });

            Route::get('one', function(){
                return 'one';
            });
        });

        $this->assertContains('one', $this->get('/prefix/one')->getContent());
        $this->assertContains('fallback', $this->get('/prefix/non-existing')->getContent());
        $this->assertContains('Page Not Found', $this->get('/non-existing')->getContent());
    }

    public function test_fallback_with_wildcards()
    {
        Route::fallback(function () {
            return response('fallback', 404);
        });

        Route::get('{any}', function(){
            return 'wildcard';
        })->where('any', '.*');

        Route::get('one', function(){
            return 'one';
        });

        $this->assertContains('one', $this->get('/one')->getContent());
        $this->assertContains('wildcard', $this->get('/non-existing')->getContent());
        $this->assertEquals(200, $this->get('/non-existing')->getStatusCode());
    }
}
