<?php

namespace Illuminate\Tests\Integration\Http;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class StreamResponseTest extends TestCase
{
    public function testDirectStreamResponse()
    {
        Route::get('/stream-response', function () {
            $factory = new HttpFactory;

            $stream = $factory->createStream('Hello World');

            return response()->directStreamDownload($stream, 'test.txt');
        });

        $response = $this->get('/stream-response')
            ->assertSuccessful()
            ->streamedContent();

        $this->assertSame('Hello World', $response);
    }
}
