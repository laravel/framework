<?php

namespace Illuminate\Tests\Http\Client\Exceptions;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class RequestExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RequestException::truncate();
    }

    protected function tearDown(): void
    {
        RequestException::truncate();

        parent::tearDown();
    }

    public function testExceptionIsInstanceOfHttpClientException(): void
    {
        $exception = new RequestException(new Response(new Psr7Response(500)));

        $this->assertInstanceOf(HttpClientException::class, $exception);
    }

    public function testExceptionHoldsResponseAndStatusCodeAsCode(): void
    {
        $response = new Response(new Psr7Response(403));

        $exception = new RequestException($response);

        $this->assertSame($response, $exception->response);
        $this->assertSame(403, $exception->getCode());
        $this->assertSame('HTTP request returned status code 403', $exception->getMessage());
    }

    public function testExceptionAppendsBodySummaryOnReport(): void
    {
        $response = new Response(new Psr7Response(403, [], 'Forbidden.'));

        $exception = new RequestException($response);
        $exception->report();

        $this->assertSame("HTTP request returned status code 403:\nForbidden.\n", $exception->getMessage());
        $this->assertTrue($exception->hasBeenSummarized);
    }

    public function testExceptionTruncatesBodySummaryAtGlobalLimit(): void
    {
        RequestException::truncateAt(5);

        $response = new Response(new Psr7Response(403, [], 'Forbidden.'));

        $exception = new RequestException($response);
        $exception->report();

        $this->assertSame("HTTP request returned status code 403:\nForbi (truncated...)\n", $exception->getMessage());
    }

    public function testExceptionTruncatesBodySummaryAtInstanceLimit(): void
    {
        $response = new Response(new Psr7Response(403, [], 'Forbidden.'));

        $exception = new RequestException($response, 5);
        $exception->report();

        $this->assertSame("HTTP request returned status code 403:\nForbi (truncated...)\n", $exception->getMessage());
    }

    public function testDontTruncateDisablesSummaryTruncation(): void
    {
        RequestException::dontTruncate();

        $this->assertFalse(RequestException::$truncateAt);
    }

    public function testTruncateResetsGlobalLimitToDefault(): void
    {
        RequestException::truncateAt(5);
        RequestException::truncate();

        $this->assertSame(120, RequestException::$truncateAt);
    }
}
