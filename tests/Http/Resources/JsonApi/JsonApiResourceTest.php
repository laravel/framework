<?php

namespace Illuminate\Tests\Http\Resources\JsonApi;

use BadMethodCallException;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\Exceptions\ResourceIdentificationException;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('unidentifiableResourceProvider')]
    public function testResolvingTheIdentifierOfAnUnidentifiableResourceThrowsAResourceIdentificationException($resource)
    {
        $this->expectException(ResourceIdentificationException::class);

        (new JsonApiResource($resource))->resolveResourceIdentifier(JsonApiRequest::create('/'));
    }

    public static function unidentifiableResourceProvider()
    {
        return [
            'array' => [['id' => 1, 'name' => 'Taylor']],
            'integer' => [5],
            'null' => [null],
            'string' => ['Taylor'],
            'object without a getKey() method' => [(object) ['id' => 1]],
        ];
    }
}
