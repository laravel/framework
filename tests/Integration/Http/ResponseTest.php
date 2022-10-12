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
}
