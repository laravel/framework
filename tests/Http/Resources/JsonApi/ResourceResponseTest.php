<?php

namespace Illuminate\Tests\Http\Resources\JsonApi;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Http\Resources\JsonApi\ResourceResponse;
use PHPUnit\Framework\TestCase;

class ResourceResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        JsonApiResource::flushState();
    }

    public function testResponseWrapperIsHardCodedToData()
    {
        JsonApiResource::wrap('laravel');

        $this->assertSame('data', (new class([]) extends ResourceResponse {
            public function getWrapper() {
                return $this->wrapper();
            }
        })->getWrapper());
    }
}
