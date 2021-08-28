<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
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

    public function testTemporarySignedOnceUrls()
    {
        Route::get('/foo/{id}', function ($id) {
            return $id;
        })->name('foo')->middleware('signed:absolute,once');

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertIsString($url = URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1]));

        $this->get(URL::signedRoute('foo', ['id' => 1]))->assertStatus(403);
        $this->assertSame('1', $this->get($url)->assertOk()->original);
        $this->get($url)->assertStatus(403);

        Carbon::setTestNow(Carbon::create(2018, 1, 1)->addMinutes(10));
        $this->get($url)->assertStatus(403);
    }

    public function testTemporarySignedOnceRelativeUrls()
    {
        Route::get('/foo/relative/{id}', function ($id) {
            return $id;
        })->name('foo')->middleware('signed:relative,once');

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertIsString(
            $url = 'https://fake.test'.URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1], false)
        );

        $this->get('https://fake.test'.URL::signedRoute('foo', ['id' => 1], null, false))->assertStatus(403);
        $this->assertSame('1', $this->get($url)->assertOk()->original);
        $this->get($url)->assertStatus(403);

        Carbon::setTestNow(Carbon::create(2018, 1, 1)->addMinutes(10));
        $this->get($url)->assertStatus(403);

        $this->get('foo/relative/1')->assertStatus(403);
    }

    public function testTemporarySignedOnceWithPrefixAndStore()
    {
        ValidateSignature::$prefix = 'bar';

        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        Config::set('cache.signed', 'foo');

        $cacheKey = 'bar:cc69b6ae281eb37edc5aa63b772e94e0192767998827cb64df79c72b0d460921';

        $cache = $this->mock(Repository::class);
        $cache->shouldReceive('has')->once()->with($cacheKey)->andReturnFalse();
        $cache->shouldReceive('has')->once()->with($cacheKey)->andReturnTrue();
        $cache->shouldReceive('put')->once()
            ->withArgs(function ($key, $value, $ttl) use ($cacheKey) {
                return $key === $cacheKey
                    && $value === true
                    && $ttl->getTimestamp() === now()->addMinutes(5)->getTimestamp();
            })
            ->andReturnTrue();

        $this->mock('cache')->shouldReceive('store')->with('foo')->times(2)->andReturn($cache);

        Route::get('/foo/{id}', function ($id) {
            return $id;
        })->name('foo')->middleware('signed:absolute,once');

        $this->assertIsString($url = URL::temporarySignedRoute('foo', now()->addMinutes(5), ['id' => 1]));

        $this->get(URL::signedRoute('foo', ['id' => 1]))->assertStatus(403);
        $this->assertSame('1', $this->get($url)->assertOk()->original);
        $this->get($url)->assertStatus(403);
    }

    protected function tearDown(): void
    {
        ValidateSignature::$prefix = 'signed.once';

        parent::tearDown();
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
