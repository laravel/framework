<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class JsonResponseTest extends TestCase
{
    public function testResponseWithInvalidJsonThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        Route::get('/response', function () {
            return new JsonResponse(new class implements \JsonSerializable
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

    public function testResponseSetDataPassesWithPriorJsonErrors()
    {
        $response = new JsonResponse();

        // Trigger json_last_error() to have a non-zero value...
        json_encode(['a' => acos(2)]);

        $response->setData(new class implements Jsonable
        {
            public function toJson($options = 0): string
            {
                return '{}';
            }
        });

        $this->assertJson($response->getContent());
    }
}
