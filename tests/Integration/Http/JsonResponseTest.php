<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
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
}
