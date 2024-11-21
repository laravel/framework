<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Session\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Illuminate\Tests\Database\Fixtures\Models\Money\Price;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

include_once 'Enums.php';

class HttpRequestTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testInstanceMethod()
    {
        $request = Request::create('');
        $this->assertSame($request, $request->instance());
    }

    public function testMethodMethod()
    {
        $request = Request::create('', 'GET');
        $this->assertSame('GET', $request->method());

        $request = Request::create('', 'HEAD');
        $this->assertSame('HEAD', $request->method());

        $request = Request::create('', 'POST');
        $this->assertSame('POST', $request->method());

        $request = Request::create('', 'PUT');
        $this->assertSame('PUT', $request->method());

        $request = Request::create('', 'PATCH');
        $this->assertSame('PATCH', $request->method());

        $request = Request::create('', 'DELETE');
        $this->assertSame('DELETE', $request->method());

        $request = Request::create('', 'OPTIONS');
        $this->assertSame('OPTIONS', $request->method());
    }

    public function testRootMethod()
    {
        $request = Request::create('http://example.com/foo/bar/script.php?test');
        $this->assertSame('http://example.com', $request->root());
    }

    public function testPathMethod()
    {
        $request = Request::create('');
        $this->assertSame('/', $request->path());

        $request = Request::create('/foo/bar');
        $this->assertSame('foo/bar', $request->path());
    }

    public function testDecodedPathMethod()
    {
        $request = Request::create('/foo%20bar');
        $this->assertSame('foo bar', $request->decodedPath());
    }

    #[DataProvider('segmentProvider')]
    public function testSegmentMethod($path, $segment, $expected)
    {
        $request = Request::create($path);
        $this->assertEquals($expected, $request->segment($segment, 'default'));
    }

    public static function segmentProvider()
    {
        return [
            ['', 1, 'default'],
            ['foo/bar//baz', 1, 'foo'],
            ['foo/bar//baz', 2, 'bar'],
            ['foo/bar//baz', 3, 'baz'],
        ];
    }

    #[DataProvider('segmentsProvider')]
    public function testSegmentsMethod($path, $expected)
    {
        $request = Request::create($path);
        $this->assertEquals($expected, $request->segments());

        $request = Request::create('foo/bar');
        $this->assertEquals(['foo', 'bar'], $request->segments());
    }

    public static function segmentsProvider()
    {
        return [
            ['', []],
            ['foo/bar', ['foo', 'bar']],
            ['foo/bar//baz', ['foo', 'bar', 'baz']],
            ['foo/0/bar', ['foo', '0', 'bar']],
        ];
    }

    public function testUrlMethod()
    {
        $request = Request::create('http://foo.com/foo/bar?name=taylor');
        $this->assertSame('http://foo.com/foo/bar', $request->url());

        $request = Request::create('http://foo.com/foo/bar/?');
        $this->assertSame('http://foo.com/foo/bar', $request->url());
    }

    public function testFullUrlMethod()
    {
        $request = Request::create('http://foo.com/foo/bar?name=taylor');
        $this->assertSame('http://foo.com/foo/bar?name=taylor', $request->fullUrl());

        $request = Request::create('https://foo.com');
        $this->assertSame('https://foo.com', $request->fullUrl());

        $request = Request::create('https://foo.com');
        $this->assertSame('https://foo.com/?coupon=foo', $request->fullUrlWithQuery(['coupon' => 'foo']));

        $request = Request::create('https://foo.com?a=b');
        $this->assertSame('https://foo.com/?a=b', $request->fullUrl());

        $request = Request::create('https://foo.com?a=b');
        $this->assertSame('https://foo.com/?a=b&coupon=foo', $request->fullUrlWithQuery(['coupon' => 'foo']));

        $request = Request::create('https://foo.com?a=b');
        $this->assertSame('https://foo.com/?a=c', $request->fullUrlWithQuery(['a' => 'c']));

        $request = Request::create('http://foo.com/foo/bar?name=taylor');
        $this->assertSame('http://foo.com/foo/bar?name=taylor', $request->fullUrlWithQuery(['name' => 'taylor']));

        $request = Request::create('http://foo.com/foo/bar/?name=taylor');
        $this->assertSame('http://foo.com/foo/bar?name=graham', $request->fullUrlWithQuery(['name' => 'graham']));

        $request = Request::create('https://foo.com');
        $this->assertSame('https://foo.com/?key=value%20with%20spaces', $request->fullUrlWithQuery(['key' => 'value with spaces']));
    }

    public function testIsMethod()
    {
        $request = Request::create('/foo/bar');

        $this->assertTrue($request->is('foo*'));
        $this->assertFalse($request->is('bar*'));
        $this->assertTrue($request->is('*bar*'));
        $this->assertTrue($request->is('bar*', 'foo*', 'baz'));

        $request = Request::create('/');

        $this->assertTrue($request->is('/'));
    }

    public function testFullUrlIsMethod()
    {
        $request = Request::create('http://example.com/foo/bar');

        $this->assertTrue($request->fullUrlIs('http://example.com/foo/bar'));
        $this->assertFalse($request->fullUrlIs('example.com*'));
        $this->assertTrue($request->fullUrlIs('http://*'));
        $this->assertTrue($request->fullUrlIs('*foo*'));
        $this->assertTrue($request->fullUrlIs('*bar'));
        $this->assertTrue($request->fullUrlIs('*'));
    }

    public function testRouteIsMethod()
    {
        $request = Request::create('/foo/bar');

        $this->assertFalse($request->routeIs('foo.bar'));

        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });

        $this->assertTrue($request->routeIs('foo.bar'));
        $this->assertTrue($request->routeIs('foo*', '*bar'));
        $this->assertFalse($request->routeIs('foo.foo'));
    }

    public function testRouteMethod()
    {
        $request = Request::create('/foo/bar');

        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/{required}/{optional?}', []);
            $route->bind($request);

            return $route;
        });

        $this->assertSame('bar', $request->route('required'));
        $this->assertSame('bar', $request->route('required', 'default'));
        $this->assertNull($request->route('optional'));
        $this->assertSame('default', $request->route('optional', 'default'));
    }

    public function testAjaxMethod()
    {
        $request = Request::create('/');
        $this->assertFalse($request->ajax());
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], '{}');
        $this->assertTrue($request->ajax());
        $request = Request::create('/', 'POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($request->ajax());
        $request->headers->set('X-Requested-With', '');
        $this->assertFalse($request->ajax());
    }

    public function testPrefetchMethod()
    {
        $request = Request::create('/');
        $this->assertFalse($request->prefetch());

        $request->server->set('HTTP_X_MOZ', '');
        $this->assertFalse($request->prefetch());
        $request->server->set('HTTP_X_MOZ', 'prefetch');
        $this->assertTrue($request->prefetch());
        $request->server->set('HTTP_X_MOZ', 'Prefetch');
        $this->assertTrue($request->prefetch());

        $request->server->remove('HTTP_X_MOZ');

        $request->headers->set('Purpose', '');
        $this->assertFalse($request->prefetch());
        $request->headers->set('Purpose', 'prefetch');
        $this->assertTrue($request->prefetch());
        $request->headers->set('Purpose', 'Prefetch');
        $this->assertTrue($request->prefetch());

        $request->headers->remove('Purpose');

        $request->headers->set('Sec-Purpose', '');
        $this->assertFalse($request->prefetch());
        $request->headers->set('Sec-Purpose', 'prefetch');
        $this->assertTrue($request->prefetch());
        $request->headers->set('Sec-Purpose', 'Prefetch');
        $this->assertTrue($request->prefetch());
    }

    public function testPjaxMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_X_PJAX' => 'true'], '{}');
        $this->assertTrue($request->pjax());
        $request->headers->set('X-PJAX', 'false');
        $this->assertTrue($request->pjax());
        $request->headers->set('X-PJAX', null);
        $this->assertFalse($request->pjax());
        $request->headers->set('X-PJAX', '');
        $this->assertFalse($request->pjax());
    }

    public function testSecureMethod()
    {
        $request = Request::create('http://example.com');
        $this->assertFalse($request->secure());
        $request = Request::create('https://example.com');
        $this->assertTrue($request->secure());
    }

    public function testUserAgentMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Laravel',
        ]);

        $this->assertSame('Laravel', $request->userAgent());
    }

    public function testHostMethod()
    {
        $request = Request::create('http://example.com');
        $this->assertSame('example.com', $request->host());

        $request = Request::create('https://example.com');
        $this->assertSame('example.com', $request->host());

        $request = Request::create('https://example.com:8080');
        $this->assertSame('example.com', $request->host());

        $request = Request::create('http://example.com:8080');
        $this->assertSame('example.com', $request->host());
    }

    public function testHttpHostMethod()
    {
        $request = Request::create('http://example.com');
        $this->assertSame('example.com', $request->httpHost());

        $request = Request::create('https://example.com');
        $this->assertSame('example.com', $request->httpHost());

        $request = Request::create('http://example.com:8080');
        $this->assertSame('example.com:8080', $request->httpHost());

        $request = Request::create('https://example.com:8080');
        $this->assertSame('example.com:8080', $request->httpHost());
    }

    public function testSchemeAndHttpHostMethod()
    {
        $request = Request::create('http://example.com');
        $this->assertSame('http://example.com', $request->schemeAndHttpHost());

        $request = Request::create('https://example.com');
        $this->assertSame('https://example.com', $request->schemeAndHttpHost());

        $request = Request::create('http://example.com:8080');
        $this->assertSame('http://example.com:8080', $request->schemeAndHttpHost());

        $request = Request::create('https://example.com:8080');
        $this->assertSame('https://example.com:8080', $request->schemeAndHttpHost());
    }

    public function testHasMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => '', 'city' => null]);
        $this->assertTrue($request->has('name'));
        $this->assertTrue($request->has('age'));
        $this->assertTrue($request->has('city'));
        $this->assertFalse($request->has('foo'));
        $this->assertFalse($request->has('name', 'email'));

        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $this->assertTrue($request->has('name'));
        $this->assertTrue($request->has('name', 'email'));
        $this->assertTrue($request->has(['name', 'email']));

        $request = Request::create('/', 'GET', ['foo' => ['bar', 'bar']]);
        $this->assertTrue($request->has('foo'));

        $request = Request::create('/', 'GET', ['foo' => '', 'bar' => null]);
        $this->assertTrue($request->has('foo'));
        $this->assertTrue($request->has('bar'));

        $request = Request::create('/', 'GET', ['foo' => ['bar' => null, 'baz' => '']]);
        $this->assertTrue($request->has('foo.bar'));
        $this->assertTrue($request->has('foo.baz'));
    }

    public function testWhenHasMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => '', 'city' => null]);

        $name = $age = $city = $foo = $bar = false;

        $request->whenHas('name', function ($value) use (&$name) {
            $name = $value;
        });

        $request->whenHas('age', function ($value) use (&$age) {
            $age = $value;
        });

        $request->whenHas('city', function ($value) use (&$city) {
            $city = $value;
        });

        $request->whenHas('foo', function () use (&$foo) {
            $foo = 'test';
        });

        $request->whenHas('bar', function () use (&$bar) {
            $bar = 'test';
        }, function () use (&$bar) {
            $bar = true;
        });

        $this->assertSame('Taylor', $name);
        $this->assertSame('', $age);
        $this->assertNull($city);
        $this->assertFalse($foo);
        $this->assertTrue($bar);
    }

    public function testWhenFilledMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => '', 'city' => null]);

        $name = $age = $city = $foo = $bar = false;

        $request->whenFilled('name', function ($value) use (&$name) {
            $name = $value;
        });

        $request->whenFilled('age', function ($value) use (&$age) {
            $age = 'test';
        });

        $request->whenFilled('city', function ($value) use (&$city) {
            $city = 'test';
        });

        $request->whenFilled('foo', function () use (&$foo) {
            $foo = 'test';
        });

        $request->whenFilled('bar', function () use (&$bar) {
            $bar = 'test';
        }, function () use (&$bar) {
            $bar = true;
        });

        $this->assertSame('Taylor', $name);
        $this->assertFalse($age);
        $this->assertFalse($city);
        $this->assertFalse($foo);
        $this->assertTrue($bar);
    }

    public function testMissingMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => '', 'city' => null]);
        $this->assertFalse($request->missing('name'));
        $this->assertFalse($request->missing('age'));
        $this->assertFalse($request->missing('city'));
        $this->assertTrue($request->missing('foo'));
        $this->assertTrue($request->missing('name', 'email'));
        $this->assertTrue($request->missing(['name', 'email']));

        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $this->assertFalse($request->missing('name'));
        $this->assertFalse($request->missing('name', 'email'));

        $request = Request::create('/', 'GET', ['foo' => ['bar', 'bar']]);
        $this->assertFalse($request->missing('foo'));

        $request = Request::create('/', 'GET', ['foo' => '', 'bar' => null]);
        $this->assertFalse($request->missing('foo'));
        $this->assertFalse($request->missing('bar'));

        $request = Request::create('/', 'GET', ['foo' => ['bar' => null, 'baz' => '']]);
        $this->assertFalse($request->missing('foo.bar'));
        $this->assertFalse($request->missing('foo.baz'));
    }

    public function testWhenMissingMethod()
    {
        $request = Request::create('/', 'GET', ['bar' => null]);

        $name = $age = $city = $foo = $bar = true;

        $request->whenMissing('name', function ($value) use (&$name) {
            $name = 'Taylor';
        });

        $request->whenMissing('age', function ($value) use (&$age) {
            $age = '';
        });

        $request->whenMissing('city', function ($value) use (&$city) {
            $city = null;
        });

        $request->whenMissing('foo', function () use (&$foo) {
            $foo = false;
        });

        $request->whenMissing('bar', function () use (&$bar) {
            $bar = 'test';
        }, function () use (&$bar) {
            $bar = true;
        });

        $this->assertSame('Taylor', $name);
        $this->assertSame('', $age);
        $this->assertNull($city);
        $this->assertFalse($foo);
        $this->assertTrue($bar);
    }

    public function testHasAnyMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => '', 'city' => null]);
        $this->assertTrue($request->hasAny('name'));
        $this->assertTrue($request->hasAny('age'));
        $this->assertTrue($request->hasAny('city'));
        $this->assertFalse($request->hasAny('foo'));
        $this->assertTrue($request->hasAny('name', 'email'));
        $this->assertTrue($request->hasAny(['name', 'email']));

        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $this->assertTrue($request->hasAny('name', 'email'));
        $this->assertFalse($request->hasAny('surname', 'password'));
        $this->assertFalse($request->hasAny(['surname', 'password']));

        $request = Request::create('/', 'GET', ['foo' => ['bar' => null, 'baz' => '']]);
        $this->assertTrue($request->hasAny('foo.bar'));
        $this->assertTrue($request->hasAny('foo.baz'));
        $this->assertFalse($request->hasAny('foo.bax'));
        $this->assertTrue($request->hasAny(['foo.bax', 'foo.baz']));
    }

    public function testFilledMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => '', 'city' => null]);
        $this->assertTrue($request->filled('name'));
        $this->assertFalse($request->filled('age'));
        $this->assertFalse($request->filled('city'));
        $this->assertFalse($request->filled('foo'));
        $this->assertFalse($request->filled('name', 'email'));

        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $this->assertTrue($request->filled('name'));
        $this->assertTrue($request->filled('name', 'email'));
        $this->assertTrue($request->filled(['name', 'email']));

        // test arrays within query string
        $request = Request::create('/', 'GET', ['foo' => ['bar', 'baz']]);
        $this->assertTrue($request->filled('foo'));

        $request = Request::create('/', 'GET', ['foo' => ['bar' => 'baz']]);
        $this->assertTrue($request->filled('foo.bar'));
    }

    public function testIsNotFilledMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => '', 'city' => null]);
        $this->assertFalse($request->isNotFilled('name'));
        $this->assertTrue($request->isNotFilled('age'));
        $this->assertTrue($request->isNotFilled('city'));
        $this->assertTrue($request->isNotFilled('foo'));
        $this->assertFalse($request->isNotFilled(['name', 'email']));
        $this->assertTrue($request->isNotFilled(['foo', 'age']));
        $this->assertTrue($request->isNotFilled(['age', 'city']));

        $request = Request::create('/', 'GET', ['foo' => ['bar', 'baz' => '0']]);
        $this->assertFalse($request->isNotFilled('foo'));
        $this->assertTrue($request->isNotFilled('foo.bar'));
        $this->assertFalse($request->isNotFilled('foo.baz'));
    }

    public function testFilledAnyMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => '', 'city' => null]);

        $this->assertTrue($request->anyFilled(['name']));
        $this->assertTrue($request->anyFilled('name'));

        $this->assertFalse($request->anyFilled(['age']));
        $this->assertFalse($request->anyFilled('age'));

        $this->assertFalse($request->anyFilled(['foo']));
        $this->assertFalse($request->anyFilled('foo'));

        $this->assertTrue($request->anyFilled(['age', 'name']));
        $this->assertTrue($request->anyFilled('age', 'name'));

        $this->assertTrue($request->anyFilled(['foo', 'name']));
        $this->assertTrue($request->anyFilled('foo', 'name'));

        $this->assertFalse($request->anyFilled(['age', 'city']));
        $this->assertFalse($request->anyFilled('age', 'city'));

        $this->assertFalse($request->anyFilled(['foo', 'bar']));
        $this->assertFalse($request->anyFilled('foo', 'bar'));
    }

    public function testInputMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $this->assertSame('Taylor', $request->input('name'));
        $this->assertSame('Taylor', $request['name']);
        $this->assertSame('Bob', $request->input('foo', 'Bob'));

        $request = Request::create('/', 'GET', [], [], ['file' => new SymfonyUploadedFile(__FILE__, 'foo.php')]);
        $this->assertInstanceOf(SymfonyUploadedFile::class, $request['file']);
    }

    public function testStringMethod()
    {
        $request = Request::create('/', 'GET', [
            'int' => 123,
            'int_str' => '456',
            'float' => 123.456,
            'float_str' => '123.456',
            'float_zero' => 0.000,
            'float_str_zero' => '0.000',
            'str' => 'abc',
            'empty_str' => '',
            'null' => null,
        ]);
        $this->assertTrue($request->string('int') instanceof Stringable);
        $this->assertTrue($request->string('unknown_key') instanceof Stringable);
        $this->assertSame('123', $request->string('int')->value());
        $this->assertSame('456', $request->string('int_str')->value());
        $this->assertSame('123.456', $request->string('float')->value());
        $this->assertSame('123.456', $request->string('float_str')->value());
        $this->assertSame('0', $request->string('float_zero')->value());
        $this->assertSame('0.000', $request->string('float_str_zero')->value());
        $this->assertSame('', $request->string('empty_str')->value());
        $this->assertSame('', $request->string('null')->value());
        $this->assertSame('', $request->string('unknown_key')->value());
    }

    public function testBooleanMethod()
    {
        $request = Request::create('/', 'GET', ['with_trashed' => 'false', 'download' => true, 'checked' => 1, 'unchecked' => '0', 'with_on' => 'on', 'with_yes' => 'yes']);
        $this->assertTrue($request->boolean('checked'));
        $this->assertTrue($request->boolean('download'));
        $this->assertFalse($request->boolean('unchecked'));
        $this->assertFalse($request->boolean('with_trashed'));
        $this->assertFalse($request->boolean('some_undefined_key'));
        $this->assertTrue($request->boolean('with_on'));
        $this->assertTrue($request->boolean('with_yes'));
    }

    public function testIntegerMethod()
    {
        $request = Request::create('/', 'GET', [
            'int' => '123',
            'raw_int' => 456,
            'zero_padded' => '078',
            'space_padded' => ' 901',
            'nan' => 'nan',
            'mixed' => '1ab',
            'underscore_notation' => '2_000',
            'null' => null,
        ]);
        $this->assertSame(123, $request->integer('int'));
        $this->assertSame(456, $request->integer('raw_int'));
        $this->assertSame(78, $request->integer('zero_padded'));
        $this->assertSame(901, $request->integer('space_padded'));
        $this->assertSame(0, $request->integer('nan'));
        $this->assertSame(1, $request->integer('mixed'));
        $this->assertSame(2, $request->integer('underscore_notation'));
        $this->assertSame(123456, $request->integer('unknown_key', 123456));
        $this->assertSame(0, $request->integer('null'));
        $this->assertSame(0, $request->integer('null', 123456));
    }

    public function testFloatMethod()
    {
        $request = Request::create('/', 'GET', [
            'float' => '1.23',
            'raw_float' => 45.6,
            'decimal_only' => '.6',
            'zero_padded' => '0.78',
            'space_padded' => ' 90.1',
            'nan' => 'nan',
            'mixed' => '1.ab',
            'scientific_notation' => '1e3',
            'null' => null,
        ]);
        $this->assertSame(1.23, $request->float('float'));
        $this->assertSame(45.6, $request->float('raw_float'));
        $this->assertSame(.6, $request->float('decimal_only'));
        $this->assertSame(0.78, $request->float('zero_padded'));
        $this->assertSame(90.1, $request->float('space_padded'));
        $this->assertSame(0.0, $request->float('nan'));
        $this->assertSame(1.0, $request->float('mixed'));
        $this->assertSame(1e3, $request->float('scientific_notation'));
        $this->assertSame(123.456, $request->float('unknown_key', 123.456));
        $this->assertSame(0.0, $request->float('null'));
        $this->assertSame(0.0, $request->float('null', 123.456));
    }

    public function testCollectMethod()
    {
        $request = Request::create('/', 'GET', ['users' => [1, 2, 3]]);

        $this->assertInstanceOf(Collection::class, $request->collect('users'));
        $this->assertTrue($request->collect('developers')->isEmpty());
        $this->assertEquals([1, 2, 3], $request->collect('users')->all());
        $this->assertEquals(['users' => [1, 2, 3]], $request->collect()->all());

        $request = Request::create('/', 'GET', ['text-payload']);
        $this->assertEquals(['text-payload'], $request->collect()->all());

        $request = Request::create('/', 'GET', ['email' => 'test@example.com']);
        $this->assertEquals(['test@example.com'], $request->collect('email')->all());

        $request = Request::create('/', 'GET', []);
        $this->assertInstanceOf(Collection::class, $request->collect());
        $this->assertTrue($request->collect()->isEmpty());

        $request = Request::create('/', 'GET', ['users' => [1, 2, 3], 'roles' => [4, 5, 6], 'foo' => ['bar', 'baz'], 'email' => 'test@example.com']);
        $this->assertInstanceOf(Collection::class, $request->collect(['users']));
        $this->assertTrue($request->collect(['developers'])->isEmpty());
        $this->assertTrue($request->collect(['roles'])->isNotEmpty());
        $this->assertEquals(['roles' => [4, 5, 6]], $request->collect(['roles'])->all());
        $this->assertEquals(['users' => [1, 2, 3], 'email' => 'test@example.com'], $request->collect(['users', 'email'])->all());
        $this->assertEquals(collect(['roles' => [4, 5, 6], 'foo' => ['bar', 'baz']]), $request->collect(['roles', 'foo']));
        $this->assertEquals(['users' => [1, 2, 3], 'roles' => [4, 5, 6], 'foo' => ['bar', 'baz'], 'email' => 'test@example.com'], $request->collect()->all());
    }

    public function testDateMethod()
    {
        $request = Request::create('/', 'GET', [
            'as_null' => null,
            'as_invalid' => 'invalid',

            'as_datetime' => '20-01-01 16:30:25',
            'as_format' => '1577896225',
            'as_timezone' => '20-01-01 13:30:25',

            'as_date' => '2020-01-01',
            'as_time' => '16:30:25',
        ]);

        $current = Carbon::create(2020, 1, 1, 16, 30, 25);

        $this->assertNull($request->date('as_null'));
        $this->assertNull($request->date('doesnt_exists'));

        $this->assertEquals($current, $request->date('as_datetime'));
        $this->assertEquals($current->format('Y-m-d H:i:s P'), $request->date('as_format', 'U')->format('Y-m-d H:i:s P'));
        $this->assertEquals($current, $request->date('as_timezone', null, 'America/Santiago'));

        $this->assertTrue($request->date('as_date')->isSameDay($current));
        $this->assertTrue($request->date('as_time')->isSameSecond('16:30:25'));
    }

    public function testDateMethodExceptionWhenValueInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $request = Request::create('/', 'GET', [
            'date' => 'invalid',
        ]);

        $request->date('date');
    }

    public function testDateMethodExceptionWhenFormatInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $request = Request::create('/', 'GET', [
            'date' => '20-01-01 16:30:25',
        ]);

        $request->date('date', 'invalid_format');
    }

    public function testEnumMethod()
    {
        $request = Request::create('/', 'GET', [
            'valid_enum_value' => 'test',
            'invalid_enum_value' => 'invalid',
            'empty_value_request' => '',
            'string' => [
                'minus_1' => '-1',
                '0' => '0',
                'plus_1' => '1',
                'doesnt_exist' => '-1024',
            ],
            'int' => [
                'minus_1' => -1,
                '0' => 0,
                'plus_1' => 1,
                'doesnt_exist' => 1024,
            ],
        ]);

        $this->assertNull($request->enum('doesnt_exist', TestEnumBacked::class));

        $this->assertEquals(TestEnumBacked::test, $request->enum('valid_enum_value', TestEnumBacked::class));

        $this->assertNull($request->enum('invalid_enum_value', TestEnumBacked::class));
        $this->assertNull($request->enum('empty_value_request', TestEnumBacked::class));
        $this->assertNull($request->enum('valid_enum_value', TestEnum::class));

        $this->assertEquals(TestIntegerEnumBacked::minus_1, $request->enum('string.minus_1', TestIntegerEnumBacked::class));
        $this->assertEquals(TestIntegerEnumBacked::zero, $request->enum('string.0', TestIntegerEnumBacked::class));
        $this->assertEquals(TestIntegerEnumBacked::plus_1, $request->enum('string.plus_1', TestIntegerEnumBacked::class));
        $this->assertNull($request->enum('string.doesnt_exist', TestIntegerEnumBacked::class));
        $this->assertEquals(TestIntegerEnumBacked::minus_1, $request->enum('int.minus_1', TestIntegerEnumBacked::class));
        $this->assertEquals(TestIntegerEnumBacked::zero, $request->enum('int.0', TestIntegerEnumBacked::class));
        $this->assertEquals(TestIntegerEnumBacked::plus_1, $request->enum('int.plus_1', TestIntegerEnumBacked::class));
        $this->assertNull($request->enum('int.doesnt_exist', TestIntegerEnumBacked::class));
    }

    public function testEnumsMethod()
    {
        $request = Request::create('/', 'GET', [
            'valid_enum_values' => ['test', 'test'],
            'invalid_enum_values' => ['invalid', 'invalid'],
            'empty_value_request' => [],
            'string' => [
                'minus_1' => ['-1', '0'],
                '0' => '0',
                'plus_1' => '1',
                'doesnt_exist' => '-1024',
            ],
            'int' => [
                'minus_1' => -1,
                '0' => 0,
                'plus_1' => 1,
                'doesnt_exist' => 1024,
            ],
        ]);

        $this->assertEmpty($request->enums('doesnt_exist', TestEnumBacked::class));

        $this->assertEquals([TestEnumBacked::test, TestEnumBacked::test], $request->enums('valid_enum_values', TestEnumBacked::class));

        $this->assertEmpty($request->enums('invalid_enum_value', TestEnumBacked::class));
        $this->assertEmpty($request->enums('empty_value_request', TestEnumBacked::class));
        $this->assertEmpty($request->enums('valid_enum_value', TestEnum::class));

        $this->assertEquals([TestIntegerEnumBacked::minus_1, TestIntegerEnumBacked::zero], $request->enums('string.minus_1', TestIntegerEnumBacked::class));
        $this->assertEquals([TestIntegerEnumBacked::zero], $request->enums('string.0', TestIntegerEnumBacked::class));
        $this->assertEquals([TestIntegerEnumBacked::plus_1], $request->enums('string.plus_1', TestIntegerEnumBacked::class));
        $this->assertEmpty($request->enums('string.doesnt_exist', TestIntegerEnumBacked::class));
        $this->assertEquals([TestIntegerEnumBacked::minus_1], $request->enums('int.minus_1', TestIntegerEnumBacked::class));
        $this->assertEquals([TestIntegerEnumBacked::zero], $request->enums('int.0', TestIntegerEnumBacked::class));
        $this->assertEquals([TestIntegerEnumBacked::plus_1], $request->enums('int.plus_1', TestIntegerEnumBacked::class));
        $this->assertEmpty($request->enums('int.doesnt_exist', TestIntegerEnumBacked::class));
    }

    public function testArrayAccess()
    {
        $request = Request::create('/', 'GET', ['name' => null, 'foo' => ['bar' => null, 'baz' => '']]);

        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar/{id}/{name}', []);
            $route->bind($request);
            $route->setParameter('id', 'foo');
            $route->setParameter('name', 'Taylor');

            return $route;
        });

        $this->assertFalse(isset($request['non-existent']));
        $this->assertNull($request['non-existent']);

        $this->assertTrue(isset($request['name']));
        $this->assertNull($request['name']);

        $this->assertNotSame('Taylor', $request['name']);

        $this->assertTrue(isset($request['foo.bar']));
        $this->assertNull($request['foo.bar']);
        $this->assertTrue(isset($request['foo.baz']));
        $this->assertSame('', $request['foo.baz']);

        $this->assertTrue(isset($request['id']));
        $this->assertSame('foo', $request['id']);
    }

    public function testArrayAccessWithoutRouteResolver()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);

        $this->assertFalse(isset($request['non-existent']));
        $this->assertNull($request['non-existent']);

        $this->assertTrue(isset($request['name']));
        $this->assertSame('Taylor', $request['name']);
    }

    public function testAllMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => null]);
        $this->assertEquals(['name' => 'Taylor', 'age' => null, 'email' => null], $request->all('name', 'age', 'email'));
        $this->assertEquals(['name' => 'Taylor'], $request->all('name'));
        $this->assertEquals(['name' => 'Taylor', 'age' => null], $request->all());

        $request = Request::create('/', 'GET', ['developer' => ['name' => 'Taylor', 'age' => null]]);
        $this->assertEquals(['developer' => ['name' => 'Taylor', 'skills' => null]], $request->all('developer.name', 'developer.skills'));
        $this->assertEquals(['developer' => ['name' => 'Taylor', 'skills' => null]], $request->all(['developer.name', 'developer.skills']));
        $this->assertEquals(['developer' => ['age' => null]], $request->all('developer.age'));
        $this->assertEquals(['developer' => ['skills' => null]], $request->all('developer.skills'));
        $this->assertEquals(['developer' => ['name' => 'Taylor', 'age' => null]], $request->all());
    }

    public function testKeysMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => null]);
        $this->assertEquals(['name', 'age'], $request->keys());

        $files = [
            'foo' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => __FILE__,
                'type' => 'blah',
                'error' => null,
            ],
        ];
        $request = Request::create('/', 'GET', [], [], $files);
        $this->assertEquals(['foo'], $request->keys());

        $request = Request::create('/', 'GET', ['name' => 'Taylor'], [], $files);
        $this->assertEquals(['name', 'foo'], $request->keys());
    }

    public function testOnlyMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => null]);
        $this->assertEquals(['name' => 'Taylor', 'age' => null], $request->only('name', 'age', 'email'));

        $request = Request::create('/', 'GET', ['developer' => ['name' => 'Taylor', 'age' => null]]);
        $this->assertEquals(['developer' => ['name' => 'Taylor']], $request->only('developer.name', 'developer.skills'));
        $this->assertEquals(['developer' => ['age' => null]], $request->only('developer.age'));
        $this->assertEquals([], $request->only('developer.skills'));
    }

    public function testExceptMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 25]);
        $this->assertEquals(['name' => 'Taylor'], $request->except('age'));
        $this->assertEquals([], $request->except('age', 'name'));
        $this->assertEquals([], $request->except(['age', 'name']));
    }

    public function testQueryMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $this->assertSame(['name' => 'Taylor'], $request->query());
        $this->assertSame('Taylor', $request->query('name'));
        $this->assertSame('Taylor', $request->query('name', 'Amir'));
        $this->assertSame('Bob', $request->query('foo', 'Bob'));
        $all = $request->query(null);
        $this->assertSame('Taylor', $all['name']);

        $request = Request::create('/', 'GET', ['hello' => 'world', 'user' => ['Taylor', 'Mohamed Said']]);
        $this->assertSame(['Taylor', 'Mohamed Said'], $request->query('user'));
        $this->assertSame(['hello' => 'world', 'user' => ['Taylor', 'Mohamed Said']], $request->query->all());

        $request = Request::create('/?hello=world&user[]=Taylor&user[]=Mohamed%20Said', 'GET', []);
        $this->assertSame(['Taylor', 'Mohamed Said'], $request->query('user'));
        $this->assertSame(['hello' => 'world', 'user' => ['Taylor', 'Mohamed Said']], $request->query->all());
    }

    public function testPostMethod()
    {
        $request = Request::create('/', 'POST', ['name' => 'Taylor']);
        $this->assertSame(['name' => 'Taylor'], $request->post());
        $this->assertSame('Taylor', $request->post('name'));
        $this->assertSame('Taylor', $request->post('name', 'Amir'));
        $this->assertSame('Bob', $request->post('foo', 'Bob'));
        $all = $request->post(null);
        $this->assertSame('Taylor', $all['name']);
    }

    public function testCookieMethod()
    {
        $request = Request::create('/', 'GET', [], ['name' => 'Taylor']);
        $this->assertSame(['name' => 'Taylor'], $request->cookie());
        $this->assertSame('Taylor', $request->cookie('name'));
        $this->assertSame('Taylor', $request->cookie('name', 'Amir'));
        $this->assertSame('Bob', $request->cookie('foo', 'Bob'));
        $all = $request->cookie(null);
        $this->assertSame('Taylor', $all['name']);
    }

    public function testHasCookieMethod()
    {
        $request = Request::create('/', 'GET', [], ['foo' => 'bar']);
        $this->assertTrue($request->hasCookie('foo'));
        $this->assertFalse($request->hasCookie('qu'));
    }

    public function testFileMethod()
    {
        $files = [
            'foo' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => __FILE__,
                'type' => 'blah',
                'error' => null,
            ],
        ];
        $request = Request::create('/', 'GET', [], [], $files);
        $this->assertInstanceOf(SymfonyUploadedFile::class, $request->file('foo'));
    }

    public function testHasFileMethod()
    {
        $request = Request::create('/', 'GET', [], [], []);
        $this->assertFalse($request->hasFile('foo'));

        $files = [
            'foo' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => __FILE__,
                'type' => 'blah',
                'error' => null,
            ],
        ];
        $request = Request::create('/', 'GET', [], [], $files);
        $this->assertTrue($request->hasFile('foo'));
        $this->assertFalse($request->hasFile('bar'));
    }

    public function testServerMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], ['foo' => 'bar']);
        $this->assertSame('bar', $request->server('foo'));
        $this->assertSame('bar', $request->server('foo.doesnt.exist', 'bar'));
        $all = $request->server(null);
        $this->assertSame('bar', $all['foo']);
    }

    public function testMergeMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $merge = ['buddy' => 'Dayle'];
        $request->merge($merge);
        $this->assertSame('Taylor', $request->input('name'));
        $this->assertSame('Dayle', $request->input('buddy'));
    }

    public function testMergeIfMissingMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $merge = ['boolean_setting' => 0];
        $request->mergeIfMissing($merge);
        $this->assertSame('Taylor', $request->input('name'));
        $this->assertSame(0, $request->input('boolean_setting'));

        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'boolean_setting' => 1]);
        $merge = ['boolean_setting' => 0];
        $request->mergeIfMissing($merge);
        $this->assertSame('Taylor', $request->input('name'));
        $this->assertSame(1, $request->input('boolean_setting'));
    }

    public function testReplaceMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $replace = ['buddy' => 'Dayle'];
        $request->replace($replace);
        $this->assertNull($request->input('name'));
        $this->assertSame('Dayle', $request->input('buddy'));
    }

    public function testOffsetUnsetMethod()
    {
        $request = Request::create('/', 'HEAD', ['name' => 'Taylor']);
        $request->offsetUnset('name');
        $this->assertNull($request->input('name'));
    }

    public function testHeaderMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_DO_THIS' => 'foo']);
        $this->assertSame('foo', $request->header('do-this'));
        $this->assertSame('default', $request->header('do-that', 'default'));
        $all = $request->header(null);
        $this->assertSame('foo', $all['do-this'][0]);
    }

    public function testBearerTokenMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer fooBearerbar']);
        $this->assertSame('fooBearerbar', $request->bearerToken());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'bearer fooBearerbar']);
        $this->assertSame('fooBearerbar', $request->bearerToken());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic foo, Bearer bar']);
        $this->assertSame('bar', $request->bearerToken());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer foo,bar']);
        $this->assertSame('foo', $request->bearerToken());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'bearer foo,bar']);
        $this->assertSame('foo', $request->bearerToken());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'foo,bar']);
        $this->assertNull($request->bearerToken());
    }

    public function testJSONMethod()
    {
        $payload = ['name' => 'taylor'];
        $request = Request::create('/', 'GET', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        $this->assertSame('taylor', $request->json('name'));
        $this->assertSame('taylor', $request->json('name', 'Otwell'));
        $this->assertSame('Moharami', $request->json('family', 'Moharami'));
        $this->assertSame('taylor', $request->input('name'));
        $data = $request->json()->all();
        $this->assertEquals($payload, $data);
    }

    public function testJSONEmulatingPHPBuiltInServer()
    {
        $payload = ['name' => 'taylor'];
        $content = json_encode($payload);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_CONTENT_LENGTH' => strlen($content)], $content);
        $this->assertTrue($request->isJson());
        $data = $request->json()->all();
        $this->assertEquals($payload, $data);

        $data = $request->all();
        $this->assertEquals($payload, $data);
    }

    public static function getPrefersCases()
    {
        return [
            ['application/json', ['json'], 'json'],
            ['application/json', ['html', 'json'], 'json'],
            ['application/foo+json', 'application/foo+json', 'application/foo+json'],
            ['application/foo+json', 'json', 'json'],
            ['application/json;q=0.5, text/html;q=1.0', ['json', 'html'], 'html'],
            ['application/json;q=0.5, text/plain;q=1.0, text/html;q=1.0', ['json', 'txt', 'html'], 'txt'],
            ['application/*', 'json', 'json'],
            ['application/json; charset=utf-8', 'json', 'json'],
            ['application/xml; charset=utf-8', ['html', 'json'], null],
            ['application/json, text/html', ['html', 'json'], 'json'],
            ['application/json;q=0.4, text/html;q=0.6', ['html', 'json'], 'html'],

            ['application/json; charset=utf-8', 'application/json', 'application/json'],
            ['application/json, text/html', ['text/html', 'application/json'], 'application/json'],
            ['application/json;q=0.4, text/html;q=0.6', ['text/html', 'application/json'], 'text/html'],
            ['application/json;q=0.4, text/html;q=0.6', ['application/json', 'text/html'], 'text/html'],

            ['*/*; charset=utf-8', 'json', 'json'],
            ['application/*', 'application/json', 'application/json'],
            ['application/*', 'application/xml', 'application/xml'],
            ['application/*', 'text/html', null],
        ];
    }

    #[DataProvider('getPrefersCases')]
    public function testPrefersMethod($accept, $prefers, $expected)
    {
        $this->assertSame(
            $expected, Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => $accept])->prefers($prefers)
        );
    }

    public function testAllInputReturnsInputAndFiles()
    {
        $file = $this->getMockBuilder(UploadedFile::class)->setConstructorArgs([__FILE__, 'photo.jpg'])->getMock();
        $request = Request::create('/?boom=breeze', 'GET', ['foo' => 'bar'], [], ['baz' => $file]);
        $this->assertEquals(['foo' => 'bar', 'baz' => $file, 'boom' => 'breeze'], $request->all());
    }

    public function testAllInputReturnsNestedInputAndFiles()
    {
        $file = $this->getMockBuilder(UploadedFile::class)->setConstructorArgs([__FILE__, 'photo.jpg'])->getMock();
        $request = Request::create('/?boom=breeze', 'GET', ['foo' => ['bar' => 'baz']], [], ['foo' => ['photo' => $file]]);
        $this->assertEquals(['foo' => ['bar' => 'baz', 'photo' => $file], 'boom' => 'breeze'], $request->all());
    }

    public function testAllInputReturnsInputAfterReplace()
    {
        $request = Request::create('/?boom=breeze', 'GET', ['foo' => ['bar' => 'baz']]);
        $request->replace(['foo' => ['bar' => 'baz'], 'boom' => 'breeze']);
        $this->assertEquals(['foo' => ['bar' => 'baz'], 'boom' => 'breeze'], $request->all());
    }

    public function testAllInputWithNumericKeysReturnsInputAfterReplace()
    {
        $request1 = Request::create('/', 'POST', [0 => 'A', 1 => 'B', 2 => 'C']);
        $request1->replace([0 => 'A', 1 => 'B', 2 => 'C']);
        $this->assertEquals([0 => 'A', 1 => 'B', 2 => 'C'], $request1->all());

        $request2 = Request::create('/', 'POST', [1 => 'A', 2 => 'B', 3 => 'C']);
        $request2->replace([1 => 'A', 2 => 'B', 3 => 'C']);
        $this->assertEquals([1 => 'A', 2 => 'B', 3 => 'C'], $request2->all());
    }

    public function testInputWithEmptyFilename()
    {
        $invalidFiles = [
            'file' => [
                'name' => null,
                'type' => null,
                'tmp_name' => null,
                'error' => 4,
                'size' => 0,
            ],
        ];

        $baseRequest = SymfonyRequest::create('/?boom=breeze', 'GET', ['foo' => ['bar' => 'baz']], [], $invalidFiles);

        Request::createFromBase($baseRequest);
    }

    public function testMultipleFileUploadWithEmptyValue()
    {
        $invalidFiles = [
            'file' => [
                'name' => [''],
                'type' => [''],
                'tmp_name' => [''],
                'error' => [4],
                'size' => [0],
            ],
        ];

        $baseRequest = SymfonyRequest::create('/?boom=breeze', 'GET', ['foo' => ['bar' => 'baz']], [], $invalidFiles);

        $request = Request::createFromBase($baseRequest);

        $this->assertEmpty($request->files->all());
    }

    public function testOldMethodCallsSession()
    {
        $request = Request::create('/');
        $session = m::mock(Store::class);
        $session->shouldReceive('getOldInput')->once()->with('foo', 'bar')->andReturn('boom');
        $request->setLaravelSession($session);
        $this->assertSame('boom', $request->old('foo', 'bar'));
    }

    public function testOldMethodCallsSessionWhenDefaultIsArray()
    {
        $request = Request::create('/');
        $session = m::mock(Store::class);
        $session->shouldReceive('getOldInput')->once()->with('foo', ['bar'])->andReturn(['bar']);
        $request->setLaravelSession($session);
        $this->assertSame(['bar'], $request->old('foo', ['bar']));
    }

    public function testOldMethodCanGetDefaultValueFromModelByKey()
    {
        $request = Request::create('/');
        $model = m::mock(Price::class);
        $model->shouldReceive('getAttribute')->once()->with('name')->andReturn('foobar');
        $session = m::mock(Store::class);
        $session->shouldReceive('getOldInput')->once()->with('name', 'foobar')->andReturn('foobar');
        $request->setLaravelSession($session);
        $this->assertSame('foobar', $request->old('name', $model));
    }

    public function testFlushMethodCallsSession()
    {
        $request = Request::create('/');
        $session = m::mock(Store::class);
        $session->shouldReceive('flashInput')->once();
        $request->setLaravelSession($session);
        $request->flush();
    }

    public function testExpectsJson()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertTrue($request->expectsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*']);
        $this->assertFalse($request->expectsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertTrue($request->expectsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => null, 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertTrue($request->expectsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_X_PJAX' => 'true']);
        $this->assertFalse($request->expectsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->assertFalse($request->expectsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertFalse($request->expectsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_X_PJAX' => 'true']);
        $this->assertFalse($request->expectsJson());
    }

    public function testFormatReturnsAcceptableFormat()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertSame('json', $request->format());
        $this->assertTrue($request->wantsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json; charset=utf-8']);
        $this->assertSame('json', $request->format());
        $this->assertTrue($request->wantsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/atom+xml']);
        $this->assertSame('atom', $request->format());
        $this->assertFalse($request->wantsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'is/not/known']);
        $this->assertSame('html', $request->format());
        $this->assertSame('foo', $request->format('foo'));
    }

    public function testFormatReturnsAcceptsJson()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertSame('json', $request->format());
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/baz+json'));
        $this->assertTrue($request->acceptsJson());
        $this->assertFalse($request->acceptsHtml());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/foo+json']);
        $this->assertTrue($request->accepts('application/foo+json'));
        $this->assertFalse($request->accepts('application/bar+json'));
        $this->assertFalse($request->accepts('application/json'));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/*']);
        $this->assertTrue($request->accepts('application/xml'));
        $this->assertTrue($request->accepts('application/json'));
    }

    public function testFormatReturnsAcceptsHtml()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->assertSame('html', $request->format());
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->acceptsHtml());
        $this->assertFalse($request->acceptsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/*']);
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('text/plain'));
    }

    public function testFormatReturnsAcceptsAll()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*']);
        $this->assertSame('html', $request->format());
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('foo/bar'));
        $this->assertTrue($request->accepts('application/baz+xml'));
        $this->assertTrue($request->acceptsHtml());
        $this->assertTrue($request->acceptsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*']);
        $this->assertSame('html', $request->format());
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('foo/bar'));
        $this->assertTrue($request->accepts('application/baz+xml'));
        $this->assertTrue($request->acceptsHtml());
        $this->assertTrue($request->acceptsJson());
    }

    public function testFormatReturnsAcceptsMultiple()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json,text/*']);
        $this->assertTrue($request->accepts(['text/html', 'application/json']));
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('text/foo'));
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/baz+json'));
    }

    public function testFormatReturnsAcceptsCharset()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json; charset=utf-8']);
        $this->assertTrue($request->accepts(['text/html', 'application/json']));
        $this->assertFalse($request->accepts('text/html'));
        $this->assertFalse($request->accepts('text/foo'));
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/baz+json'));
    }

    public function testBadAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pt-PT; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 (.NET CLR 3.5.30729)']);
        $this->assertFalse($request->accepts(['text/html', 'application/json']));
        $this->assertFalse($request->accepts('text/html'));
        $this->assertFalse($request->accepts('text/foo'));
        $this->assertFalse($request->accepts('application/json'));
        $this->assertFalse($request->accepts('application/baz+json'));
        $this->assertFalse($request->acceptsHtml());
        $this->assertFalse($request->acceptsJson());

        // Should not be handled as regex.
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '.+/.+']);
        $this->assertFalse($request->accepts('application/json'));
        $this->assertFalse($request->accepts('application/baz+json'));

        // Should not produce compilation error on invalid regex.
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '(/(']);
        $this->assertFalse($request->accepts('text/html'));
    }

    public function testCaseInsensitiveAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'APPLICATION/JSON']);
        $this->assertTrue($request->accepts(['text/html', 'application/json']));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'AppLiCaTion/JsOn']);
        $this->assertTrue($request->accepts(['text/html', 'application/json']));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'APPLICATION/*']);
        $this->assertTrue($request->accepts(['text/html', 'application/json']));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'APPLICATION/JSON']);
        $this->assertTrue($request->expectsJson());
    }

    public function testSessionMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session store not set on request.');

        $request = Request::create('/');
        $request->session();
    }

    public function testHasSessionMethod()
    {
        $request = Request::create('/');

        $this->assertFalse($request->hasSession());

        $session = m::mock(Store::class);
        $request->setLaravelSession($session);

        $this->assertTrue($request->hasSession());
    }

    public function testGetSessionMethodWithLaravelSession()
    {
        $request = Request::create('/');

        $laravelSession = m::mock(Store::class);
        $request->setLaravelSession($laravelSession);

        $session = $request->getSession();
        $this->assertInstanceOf(SessionInterface::class, $session);

        $laravelSession->shouldReceive('start')->once()->andReturn(true);
        $session->start();
    }

    public function testGetSessionMethodWithoutLaravelSession()
    {
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionMessage('There is currently no session available.');

        $request = Request::create('/');

        $request->getSession();
    }

    public function testUserResolverMakesUserAvailableAsMagicProperty()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(function () {
            return 'user';
        });
        $this->assertSame('user', $request->user());
    }

    public function testFingerprintMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], []);
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar/{id}', []);
            $route->bind($request);

            return $route;
        });

        $this->assertEquals(40, mb_strlen($request->fingerprint()));
    }

    public function testFingerprintWithoutRoute()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to generate fingerprint. Route unavailable.');

        $request = Request::create('/', 'GET', [], [], [], []);
        $request->fingerprint();
    }

    /**
     * Ensure JSON GET requests populate $request->request with the JSON content.
     *
     * @link https://github.com/laravel/framework/pull/7052 Correctly fill the $request->request parameter bag on creation.
     */
    public function testJsonRequestFillsRequestBodyParams()
    {
        $body = [
            'foo' => 'bar',
            'baz' => ['qux'],
        ];

        $server = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $base = SymfonyRequest::create('/', 'GET', [], [], [], $server, json_encode($body));

        $request = Request::createFromBase($base);

        $this->assertEquals($request->request->all(), $body);
    }

    /**
     * Ensure non-JSON GET requests don't pollute $request->request with the GET parameters.
     *
     * @link https://github.com/laravel/framework/pull/37921 Manually populate POST request body with JSON data only when required.
     */
    public function testNonJsonRequestDoesntFillRequestBodyParams()
    {
        $params = ['foo' => 'bar'];

        $getRequest = Request::create('/', 'GET', $params, [], [], []);
        $this->assertEquals($getRequest->request->all(), []);
        $this->assertEquals($getRequest->query->all(), $params);

        $postRequest = Request::create('/', 'POST', $params, [], [], []);
        $this->assertEquals($postRequest->request->all(), $params);
        $this->assertEquals($postRequest->query->all(), []);
    }

    /**
     * Tests for Http\Request magic methods `__get()` and `__isset()`.
     *
     * @link https://github.com/laravel/framework/issues/10403 Form request object attribute returns empty when have some string.
     */
    public function testMagicMethods()
    {
        // Simulates QueryStrings.
        $request = Request::create('/', 'GET', ['foo' => 'bar', 'empty' => '']);

        // Parameter 'foo' is 'bar', then it ISSET and is NOT EMPTY.
        $this->assertSame('bar', $request->foo);
        $this->assertTrue(isset($request->foo));
        $this->assertNotEmpty($request->foo);

        // Parameter 'empty' is '', then it ISSET and is EMPTY.
        $this->assertSame('', $request->empty);
        $this->assertTrue(isset($request->empty));
        $this->assertEmpty($request->empty);

        // Parameter 'undefined' is undefined/null, then it NOT ISSET and is EMPTY.
        $this->assertNull($request->undefined);
        $this->assertFalse(isset($request->undefined));
        $this->assertEmpty($request->undefined);

        // Simulates Route parameters.
        $request = Request::create('/example/bar', 'GET', ['xyz' => 'overwritten']);
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/example/{foo}/{xyz?}/{undefined?}', []);
            $route->bind($request);

            return $route;
        });

        // Router parameter 'foo' is 'bar', then it ISSET and is NOT EMPTY.
        $this->assertSame('bar', $request->foo);
        $this->assertSame('bar', $request['foo']);
        $this->assertTrue(isset($request->foo));
        $this->assertNotEmpty($request->foo);

        // Router parameter 'undefined' is undefined/null, then it NOT ISSET and is EMPTY.
        $this->assertNull($request->undefined);
        $this->assertFalse(isset($request->undefined));
        $this->assertEmpty($request->undefined);

        // Special case: router parameter 'xyz' is 'overwritten' by QueryString, then it ISSET and is NOT EMPTY.
        // Basically, QueryStrings have priority over router parameters.
        $this->assertSame('overwritten', $request->xyz);
        $this->assertTrue(isset($request->foo));
        $this->assertNotEmpty($request->foo);

        // Simulates empty QueryString and Routes.
        $request = Request::create('/');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/', []);
            $route->bind($request);

            return $route;
        });

        // Parameter 'undefined' is undefined/null, then it NOT ISSET and is EMPTY.
        $this->assertNull($request->undefined);
        $this->assertFalse(isset($request->undefined));
        $this->assertEmpty($request->undefined);

        // Special case: simulates empty QueryString and Routes, without the Route Resolver.
        // It'll happen when you try to get a parameter outside a route.
        $request = Request::create('/');

        // Parameter 'undefined' is undefined/null, then it NOT ISSET and is EMPTY.
        $this->assertNull($request->undefined);
        $this->assertFalse(isset($request->undefined));
        $this->assertEmpty($request->undefined);
    }

    public function testHttpRequestFlashCallsSessionFlashInputWithInputData()
    {
        $session = m::mock(Store::class);
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor', 'email' => 'foo']);
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $request->setLaravelSession($session);
        $request->flash();
    }

    public function testHttpRequestFlashOnlyCallsFlashWithProperParameters()
    {
        $session = m::mock(Store::class);
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $request->setLaravelSession($session);
        $request->flashOnly(['name']);
    }

    public function testHttpRequestFlashExceptCallsFlashWithProperParameters()
    {
        $session = m::mock(Store::class);
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $request->setLaravelSession($session);
        $request->flashExcept(['email']);
    }

    public function testGeneratingJsonRequestFromParentRequestUsesCorrectType()
    {
        if (! method_exists(SymfonyRequest::class, 'getPayload')) {
            return;
        }

        $base = SymfonyRequest::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"hello":"world"}');

        $request = Request::createFromBase($base);

        $this->assertInstanceOf(InputBag::class, $request->getPayload());
        $this->assertSame('world', $request->getPayload()->get('hello'));
    }

    public function testJsonRequestsCanMergeDataIntoJsonRequest()
    {
        if (! method_exists(SymfonyRequest::class, 'getPayload')) {
            return;
        }

        $base = SymfonyRequest::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"first":"Taylor","last":"Otwell"}');
        $request = Request::createFromBase($base);

        $request->merge([
            'name' => $request->get('first').' '.$request->get('last'),
        ]);

        $this->assertSame('Taylor Otwell', $request->get('name'));
    }

    public function testItCanHaveObjectsInJsonPayload()
    {
        if (! method_exists(SymfonyRequest::class, 'getPayload')) {
            return;
        }

        $base = SymfonyRequest::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"framework":{"name":"Laravel"}}');
        $request = Request::createFromBase($base);

        $value = $request->get('framework');

        $this->assertSame(['name' => 'Laravel'], $request->get('framework'));
    }

    public function testItDoesNotGenerateJsonErrorsForEmptyContent()
    {
        // clear any existing errors
        json_encode(null);

        Request::create('', 'GET')->json();

        $this->assertTrue(json_last_error() === JSON_ERROR_NONE);
    }
}
