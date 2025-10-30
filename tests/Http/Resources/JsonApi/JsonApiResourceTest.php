<?php

namespace Illuminate\Tests\Http\Resources\JsonApi;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use PHPUnit\Framework\TestCase;

class JsonApiResourceTest extends TestCase
{
    protected function tearDown(): void
    {
        JsonResource::flushState();
        JsonApiResource::flushState();
    }

    public function testResponseWrapperIsHardCodedToData()
    {
        JsonResource::wrap('laravel');

        $this->assertSame('data', JsonApiResource::$wrap);
    }
}
