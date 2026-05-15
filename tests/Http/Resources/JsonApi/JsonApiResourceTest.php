<?php

namespace Illuminate\Tests\Http\Resources\JsonApi;

use BadMethodCallException;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use PHPUnit\Framework\TestCase;

class JsonApiResourceTest extends TestCase
{
    protected function tearDown(): void
    {
        JsonResource::flushState();
        JsonApiResource::flushState();

        parent::tearDown();
    }

    public function testResponseWrapperIsHardCodedToData()
    {
        JsonResource::wrap('laravel');

        $this->assertSame('data', JsonApiResource::$wrap);
    }

    public function testUnableToSetWrapper()
    {
        $this->expectExceptionObject(new BadMethodCallException('Using Illuminate\Http\Resources\JsonApi\JsonApiResource::wrap() method is not allowed.'));

        JsonApiResource::wrap('laravel');
    }

    public function testUnableToUnsetWrapper()
    {
        $this->expectExceptionObject(new BadMethodCallException('Using Illuminate\Http\Resources\JsonApi\JsonApiResource::withoutWrapping() method is not allowed.'));

        JsonApiResource::withoutWrapping();
    }

    public function testFlushStateResetsMaxRelationshipDepthToDefault()
    {
        $this->assertSame(5, JsonApiResource::$maxRelationshipDepth);

        JsonApiResource::maxRelationshipDepth(10);
        $this->assertSame(10, JsonApiResource::$maxRelationshipDepth);

        JsonApiResource::flushState();

        $this->assertSame(5, JsonApiResource::$maxRelationshipDepth);
    }
}
