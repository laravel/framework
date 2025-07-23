<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Http\Middleware\SetCacheHeaders as Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CacheTest extends TestCase
{
    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = (string) Cache::using('max_age=120;no-transform;s_maxage=60;');
        $this->assertSame('Illuminate\Http\Middleware\SetCacheHeaders:max_age=120;no-transform;s_maxage=60;', $signature);

        $signature = (string) Cache::using('max_age=120;no-transform;s_maxage=60');
        $this->assertSame('Illuminate\Http\Middleware\SetCacheHeaders:max_age=120;no-transform;s_maxage=60', $signature);

        $signature = (string) Cache::using([
            'max_age=120',
            'no-transform',
            's_maxage=60',
            'etag' => true,
        ]);
        $this->assertSame('Illuminate\Http\Middleware\SetCacheHeaders:max_age=120;no-transform;s_maxage=60;etag', $signature);

        $signature = (string) Cache::using([
            'max_age' => 120,
            'no-transform',
            's_maxage' => '60',
        ]);
        $this->assertSame('Illuminate\Http\Middleware\SetCacheHeaders:max_age=120;no-transform;s_maxage=60', $signature);
    }

    public function testDoNotSetHeaderWhenMethodNotCacheable()
    {
        $request = new Request;
        $request->setMethod('PUT');

        $response = (new Cache)->handle($request, function () {
            return new Response('Hello Laravel');
        }, 'max_age=120;s_maxage=60');

        $this->assertNull($response->getMaxAge());
    }

    public function testDoNotSetHeaderWhenNoContent()
    {
        $response = (new Cache)->handle(new Request, function () {
            return new Response;
        }, 'max_age=120;s_maxage=60');

        $this->assertNull($response->getMaxAge());
        $this->assertNull($response->getEtag());
    }

    public function testSetHeaderToFileResponseEvenWithNoContent()
    {
        $response = (new Cache)->handle(new Request, function () {
            $filePath = __DIR__.'/../fixtures/test.txt';

            return new BinaryFileResponse($filePath);
        }, 'max_age=120;s_maxage=60');

        $this->assertNotNull($response->getMaxAge());
    }

    public function testSetHeaderToDownloadResponseEvenWithNoContent()
    {
        $response = (new Cache)->handle(new Request, function () {
            return new StreamedResponse(function () {
                $filePath = __DIR__.'/../fixtures/test.txt';
                readfile($filePath);
            });
        }, 'max_age=120;s_maxage=60');

        $this->assertNotNull($response->getMaxAge());
    }

    public function testAddHeaders()
    {
        $response = (new Cache)->handle(new Request, function () {
            return new Response('some content');
        }, 'max_age=100;s_maxage=200;etag=ABC');

        $this->assertSame('"ABC"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
    }

    public function testAddHeadersUsingArray()
    {
        $response = (new Cache)->handle(new Request, function () {
            return new Response('some content');
        }, ['max_age' => 100, 's_maxage' => 200, 'etag' => 'ABC']);

        $this->assertSame('"ABC"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
    }

    public function testGenerateEtag()
    {
        $response = (new Cache)->handle(new Request, function () {
            return new Response('some content');
        }, 'etag;max_age=100;s_maxage=200');

        $this->assertSame('"4f1b32bff4356281946800d355007128"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
    }

    public function testDoesNotOverrideEtag()
    {
        $response = (new Cache)->handle(new Request, function () {
            return (new Response('some content'))->setEtag('XYZ');
        }, 'etag');

        $this->assertSame('"XYZ"', $response->getEtag());
    }

    public function testIsNotModified()
    {
        $request = new Request;
        $request->headers->set('If-None-Match', '"4f1b32bff4356281946800d355007128"');

        $response = (new Cache)->handle($request, function () {
            return new Response('some content');
        }, 'etag;max_age=100;s_maxage=200');

        $this->assertSame(304, $response->getStatusCode());
    }

    public function testInvalidOption()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Cache)->handle(new Request, function () {
            return new Response('some content');
        }, 'invalid');
    }

    public function testLastModifiedUnixTime()
    {
        $time = time();

        $response = (new Cache)->handle(new Request, function () {
            return new Response('some content');
        }, "last_modified=$time");

        $this->assertSame($time, $response->getLastModified()->getTimestamp());
    }

    public function testLastModifiedStringDate()
    {
        $birthdate = '1973-04-09 10:10:10';
        $response = (new Cache)->handle(new Request, function () {
            return new Response('some content');
        }, "last_modified=$birthdate");

        $this->assertSame(Carbon::parse($birthdate)->timestamp, $response->getLastModified()->getTimestamp());
    }

    public function testTrailingDelimiterIgnored()
    {
        $time = time();

        $response = (new Cache)->handle(new Request, function () {
            return new Response('some content');
        }, "last_modified=$time;");

        $this->assertSame($time, $response->getLastModified()->getTimestamp());
    }

    public function testItDoesNotSetEtagHeadersForBinaryContent()
    {
        $response = (new Cache)->handle(new Request, function () {
            return new BinaryFileResponse(__DIR__.'/../fixtures/test.txt');
        }, 'etag');

        $this->assertNull($response->getEtag());
    }
}
