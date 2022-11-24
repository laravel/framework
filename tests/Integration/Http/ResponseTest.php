<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use JsonSerializable;
use Orchestra\Testbench\TestCase;

class ResponseTest extends TestCase
{
    public function testResponseAccepted()
    {
        Route::get('/response', function () {
            return response()->accepted();
        });

        $this->get('/response')
            ->assertStatus(202)
            ->assertContent('');
    }

    public function testResponseAcceptedWithContent()
    {
        Route::get('/response', function () {
            return response()->accepted('Hello World');
        });

        $this->get('/response')
            ->assertStatus(202)
            ->assertContent('Hello World');
    }

    public function testResponseAcceptedWithHeaders()
    {
        Route::get('/response', function () {
            return response()->accepted('Hello World', [
                'X-Example' => 'X-Value',
            ]);
        });

        $this->get('/response')
            ->assertStatus(202)
            ->assertContent('Hello World')
            ->assertHeader('X-Example', 'X-Value');
    }

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
}
