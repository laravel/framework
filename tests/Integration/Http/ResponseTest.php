<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use JsonSerializable;
use Orchestra\Testbench\TestCase;

class ResponseTest extends TestCase
{
    public function testResponseWithInvalidJsonThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        Route::get('/response', function () {
            return (new Response())->setContent(new class implements JsonSerializable
            {
                public function jsonSerialize(): string
                {
                    return "\xB1\x31";
                }
            });
        });

        $this->withoutExceptionHandling();

        $this->get('/response');
    }

    public function testJsonResponseHelper(): void
    {
        $response = json_response(['message' => 'Hello World']);
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['message' => 'Hello World'], $response->getData(true));

        $response = json_response(['error' => 'Not Found'], 404);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['error' => 'Not Found'], $response->getData(true));

        $response = json_response(['data' => 'test'], 200, ['X-Custom-Header' => 'TestValue']);
        $this->assertEquals('TestValue', $response->headers->get('X-Custom-Header'));

        $data = ['url' => 'https://example.com/test'];
        $response = json_response($data, 200, [], JSON_UNESCAPED_SLASHES);
        $this->assertStringNotContainsString('\/', $response->getContent());
        $this->assertStringContainsString('https://example.com/test', $response->getContent());
    }
}
