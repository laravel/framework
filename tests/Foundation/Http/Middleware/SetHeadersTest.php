<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\SetHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class SetHeadersTest extends TestCase
{
    /**
     * @var Closure
     */
    private $next;

    public function setUp(): void
    {
        $this->next = function () {
            return new Response();
        };
    }

    public function testNoHeaders(): void
    {
        $middleware = new NoHeaders();
        $request = new Request();
        $response = $middleware->handle($request, $this->next);
        $headers = $response->headers->all();

        $this->assertSame(2, count($headers));
        $this->assertArrayHasKey('cache-control', $headers);
        $this->assertArrayHasKey('date', $headers);
    }

    public function testSingleHeader(): void
    {
        $middleware = new SingleHeader();
        $request = new Request();
        $response = $middleware->handle($request, $this->next);
        $headers = $response->headers->all();

        $this->assertSame(3, count($headers));
        $this->assertArrayHasKey('cache-control', $headers);
        $this->assertArrayHasKey('date', $headers);

        unset($headers['cache-control']);
        unset($headers['date']);

        $expected = [
            'x-frame-options' => [
                'DENY',
            ],
        ];

        $this->assertSame($expected, $headers);
    }

    public function testMultipleHeaders(): void
    {
        $middleware = new MultipleHeaders();
        $request = new Request();
        $response = $middleware->handle($request, $this->next);
        $headers = $response->headers->all();

        $this->assertSame(5, count($headers));
        $this->assertArrayHasKey('cache-control', $headers);
        $this->assertArrayHasKey('date', $headers);

        unset($headers['cache-control']);
        unset($headers['date']);

        $expected = [
            'server' => [
                'nginx',
            ],
            'x-frame-options' => [
                'DENY',
            ],
            'x-application-version' => [
                '4.2.0',
            ],
        ];

        $this->assertSame($expected, $headers);
    }

    public function testMultipleValues(): void
    {
        $middleware = new MultipleValues();
        $request = new Request();
        $response = $middleware->handle($request, $this->next);
        $headers = $response->headers->all();

        $this->assertSame(3, count($headers));
        $this->assertArrayHasKey('cache-control', $headers);
        $this->assertArrayHasKey('date', $headers);

        unset($headers['cache-control']);
        unset($headers['date']);

        $expected = [
            'x-multivalue-header' => [
                'foo',
                'bar',
                'baz',
            ],
        ];

        $this->assertSame($expected, $headers);
    }
}

class NoHeaders extends SetHeaders
{
    protected $headers = [];
}

class SingleHeader extends SetHeaders
{
    protected $headers = [
        'x-frame-options' => 'DENY',
    ];
}

class MultipleHeaders extends SetHeaders
{
    protected $headers = [
        'server' => 'nginx',
        'x-frame-options' => 'DENY',
        'x-application-version' => '4.2.0',
    ];
}

class MultipleValues extends SetHeaders
{
    protected $headers = [
        'x-multivalue-header' => [
            'foo',
            'bar',
            'baz',
        ],
    ];
}
