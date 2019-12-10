<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * @group integration
 */
class InternalRedirectTest extends TestCase
{
    public function testBasicInternalRedirect()
    {
        Route::get('foo', function () {
            return redirect()->internal('bar');
        });

        Route::get('bar', function () {
            return 'baz';
        })->name('bar');

        $this->assertStringContainsString('baz', $this->get('/foo')->getContent());
    }

    public function testInternalRedirectWithPrefix()
    {
        Route::name('prefix.')->group(function () {
            Route::get('foo', function () {
                return redirect()->internal('prefix.bar');
            });

            Route::get('bar', function () {
                return 'baz';
            })->name('bar');
        });

        $this->assertStringContainsString('baz', $this->get('/foo')->getContent());
    }

    public function testInternalRedirectRouteDoesNotExist()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route [bar] not defined for the internal redirect.');

        Route::get('foo', function () {
            return redirect()->internal('bar');
        });

        $this->withoutExceptionHandling()->get('/foo');
    }
}
