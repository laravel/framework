<?php

namespace Illuminate\Tests\Http\Resources\JsonApi;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\Exceptions\ResourceIdentificationException;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
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
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Using Illuminate\Http\Resources\JsonApi\JsonApiResource::wrap() method is not allowed.');

        JsonApiResource::wrap('laravel');
    }

    public function testUnableToUnsetWrapper()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Using Illuminate\Http\Resources\JsonApi\JsonApiResource::withoutWrapping() method is not allowed.');

        JsonApiResource::withoutWrapping();
    }

    public function testPlainJsonResourceWithArrayCannotBeIncluded()
    {
        $this->expectException(ResourceIdentificationException::class);

        $model = new class extends Model {};
        $model->id = 1;

        $resource = new JsonApiResource($model);
        $resource->loadedRelationshipsMap = [
            [new JsonResource(['id' => 1, 'name' => 'test']), 'things', '1', true],
        ];

        $resource->resolveIncludedResourceObjects(new JsonApiRequest);
    }

    public function testIncludedResourcesCanBeArrayBackedCustomResources()
    {
        $model = new class extends Model {};
        $model->id = 1;

        $resource = new JsonApiResource($model);
        $resource->loadedRelationshipsMap = [
            [new ArrayBackedJsonApiResource(['id' => 99, 'name' => 'test']), 'things', '99', true],
        ];

        $included = $resource->resolveIncludedResourceObjects(new JsonApiRequest);

        $this->assertCount(1, $included);
        $this->assertSame('99', $included[0]['id']);
        $this->assertSame('things', $included[0]['type']);
    }
}

class ArrayBackedJsonApiResource extends JsonApiResource
{
    public function toId(Request $request)
    {
        return (string) $this->resource['id'];
    }

    public function toAttributes(Request $request)
    {
        return ['name' => $this->resource['name']];
    }
}
