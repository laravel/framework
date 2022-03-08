<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class HttpClientTest extends TestCase
{
    public function testRequestsKeepQueryStringWhenReplacingOptions()
    {
        Http::fake();

        Http::withOptions([
            'query' => [
                'foo' => 'bar',
            ],
        ])->get('https://example.com');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://example.com?foo=bar';
        });
    }
}
