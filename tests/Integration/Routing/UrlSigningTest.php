<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class UrlSigningTest extends TestCase
{
    public function testSigningUrl()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        $this->assertIsString($url = URL::signedRoute('foo', ['id' => 1]));
        $this->assertSame('valid', $this->get($url)->original);
    }

    public function testSigningUrlWithCustomRouteSlug()
    {
        Route::get('/foo/{post:slug}', function (Request $request, $slug) {
            return ['slug' => $slug, 'valid' => $request->hasValidSignature() ? 'valid' : 'invalid'];
        })->name('foo');

        $model = new RoutableInterfaceStub;
        $model->routable = 'routable-slug';

        $this->assertIsString($url = URL::signedRoute('foo', ['post' => $model]));
        $this->assertSame('valid', $this->get($url)->original['valid']);
        $this->assertSame('routable-slug', $this->get($url)->original['slug']);
    }

    public function testTemporarySignedUrls()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertIsString($url = URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1]));
        $this->assertSame('valid', $this->get($url)->original);

        Carbon::setTestNow(Carbon::create(2018, 1, 1)->addMinutes(10));
        $this->assertSame('invalid', $this->get($url)->original);
    }

    public function testTemporarySignedUrlsWithExpiresParameter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1, 'expires' => 253402300799]);
    }

    public function testSignedUrlWithUrlWithoutSignatureParameter()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        $this->assertSame('invalid', $this->get('/foo/1')->original);
    }

    public function testSignedUrlWithNullParameter()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        $this->assertIsString($url = URL::signedRoute('foo', ['id' => 1, 'param']));
        $this->assertSame('valid', $this->get($url)->original);
    }

    public function testSignedUrlWithEmptyStringParameter()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        $this->assertIsString($url = URL::signedRoute('foo', ['id' => 1, 'param' => '']));
        $this->assertSame('valid', $this->get($url)->original);
    }

    public function testSignedUrlWithMultipleParameters()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        $this->assertIsString($url = URL::signedRoute('foo', ['id' => 1, 'param1' => 'value1', 'param2' => 'value2']));
        $this->assertSame('valid', $this->get($url)->original);
    }

    public function testSignedUrlWithSignatureTextInKeyOrValue()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        $this->assertIsString($url = URL::signedRoute('foo', ['id' => 1, 'custom-signature' => 'signature=value']));
        $this->assertSame('valid', $this->get($url)->original);
    }

    public function testSignedUrlWithAppendedNullParameterInvalid()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo');

        $this->assertIsString($url = URL::signedRoute('foo', ['id' => 1]));
        $this->assertSame('invalid', $this->get($url.'&appended')->original);
    }

    public function testSignedUrlParametersParsedCorrectly()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature()
                && intval($id) === 1
                && $request->has('paramEmpty')
                && $request->has('paramEmptyString')
                && $request->query('paramWithValue') === 'value'
                ? 'valid'
                : 'invalid';
        })->name('foo');

        $this->assertIsString($url = URL::signedRoute('foo', ['id' => 1,
            'paramEmpty',
            'paramEmptyString' => '',
            'paramWithValue' => 'value',
        ]));
        $this->assertSame('valid', $this->get($url)->original);
    }

    public function testSignedMiddleware()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo')->middleware(ValidateSignature::class);

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertIsString($url = URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1]));
        $this->assertSame('valid', $this->get($url)->original);
    }

    public function testSignedMiddlewareWithInvalidUrl()
    {
        Route::get('/foo/{id}', function (Request $request, $id) {
            return $request->hasValidSignature() ? 'valid' : 'invalid';
        })->name('foo')->middleware(ValidateSignature::class);

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertIsString($url = URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1]));
        Carbon::setTestNow(Carbon::create(2018, 1, 1)->addMinutes(10));

        $response = $this->get($url);
        $response->assertStatus(403);
    }

    public function testSignedMiddlewareWithRoutableParameter()
    {
        $model = new RoutableInterfaceStub;
        $model->routable = 'routable';

        Route::get('/foo/{bar}', function (Request $request, $routable) {
            return $request->hasValidSignature() ? $routable : 'invalid';
        })->name('foo');

        $this->assertIsString($url = URL::signedRoute('foo', $model));
        $this->assertSame('routable', $this->get($url)->original);
    }

    public function testSignedMiddlewareWithRelativePath()
    {
        Route::get('/foo/relative', function (Request $request) {
            return $request->hasValidSignature($absolute = false) ? 'valid' : 'invalid';
        })->name('foo')->middleware('signed:relative');

        $this->assertIsString($url = 'https://fake.test'.URL::signedRoute('foo', [], null, $absolute = false));
        $this->assertSame('valid', $this->get($url)->original);

        $response = $this->get('/foo/relative');
        $response->assertStatus(403);
    }
}

class RoutableInterfaceStub implements UrlRoutable
{
    public $key;
    public $slug = 'routable-slug';

    public function getRouteKey()
    {
        return $this->{$this->getRouteKeyName()};
    }

    public function getRouteKeyName()
    {
        return 'routable';
    }

    public function resolveRouteBinding($routeKey, $field = null)
    {
        //
    }

    public function resolveChildRouteBinding($childType, $routeKey, $field = null)
    {
        //
    }
}
