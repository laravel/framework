<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Middleware\ValidateSignature;

/**
 * @group integration
 */
class UrlSigningTest extends TestCase
{
    public function test_signing_url()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        $this->assertTrue(is_string($url = URL::signedRoute('foo', ['id' => 1])));
        $this->assertEquals('valid', $this->get($url)->original);
    }

    public function test_temporary_signed_urls()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertTrue(is_string($url = URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1])));
        $this->assertEquals('valid', $this->get($url)->original);

        Carbon::setTestNow(Carbon::create(2018, 1, 1)->addMinutes(10));
        $this->assertEquals('invalid', $this->get($url)->original);
    }

    public function test_signed_middleware()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo')->middleware(ValidateSignature::class);

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertTrue(is_string($url = URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1])));
        $this->assertEquals('valid', $this->get($url)->original);
    }

    public function test_signed_middleware_with_invalid_url()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo')->middleware(ValidateSignature::class);

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertTrue(is_string($url = URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1])));
        Carbon::setTestNow(Carbon::create(2018, 1, 1)->addMinutes(10));

        $response = $this->get($url);
        $response->assertStatus(403);
    }

    public function test_signed_middleware_with_routable_parameter()
    {
        $model = new RoutableInterfaceStub;
        $model->routable = 'routable';

        Route::get('/foo/{bar}', function (Request $request, $routable) {
            return $request->hasValidSignature() ? $routable : 'invalid';
        })->name('foo');

        $this->assertTrue(is_string($url = URL::signedRoute('foo', $model)));
        $this->assertEquals('routable', $this->get($url)->original);
    }
}

class RoutableInterfaceStub implements UrlRoutable
{
    public $key;

    public function getRouteKey()
    {
        return $this->{$this->getRouteKeyName()};
    }

    public function getRouteKeyName()
    {
        return 'routable';
    }

    public function resolveRouteBinding($routeKey)
    {
        //
    }
}
