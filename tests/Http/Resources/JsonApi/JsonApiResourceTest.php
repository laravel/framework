<?php

namespace Illuminate\Tests\Http\Resources\JsonApi;

use BadMethodCallException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class JsonApiResourceTest extends TestCase
{
    protected function tearDown(): void
    {
        JsonResource::flushState();
        JsonApiResource::flushState();
        Container::getInstance()->flush();
        Relation::morphMap([], false);
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

    public function testResourceTypeIsPickedFromMorph()
    {
        Relation::morphMap(['json-api-model' => JsonApiModel::class]);

        $model = new JsonApiModel(['id' => 1, 'name' => 'User']);

        $responseData = $this->fakeJsonApiResponseForModel($model)['data'];

        $this->assertArrayHasKey('type', $responseData);
        $this->assertSame('json-api-models', $responseData['type']);
    }

    public function testIncludedResourceDoesNotContainPrimaryKey()
    {
        Relation::morphMap(['json-api-model' => JsonApiModel::class]);

        $model = new JsonApiModel(['id' => 1, 'name' => 'User']);
        $model->setRelation('manager', new JsonApiModel(['id' => 2, 'name' => 'Manager']));
        $model->setRelation('deputy', new JsonApiModel(['id' => 2, 'email' => 'deputy@example.com']));

        $responseData = $this->fakeJsonApiResponseForModel($model);
        $this->assertArrayNotHasKey('id', $responseData['included'][0]['attributes']);
    }

    public function testIncludedMatchingResourceAttributesAreMerged()
    {
        Relation::morphMap(['json-api-model' => JsonApiModel::class]);

        $model = new JsonApiModel(['id' => 1, 'name' => 'User']);
        $model->setRelation('manager', new JsonApiModel(['id' => 2, 'name' => 'Manager']));
        $model->setRelation('deputy', new JsonApiModel(['id' => 2, 'email' => 'deputy@example.com']));

        $responseData = $this->fakeJsonApiResponseForModel($model);

        $this->assertEquals([
            'id' => '2',
            'type' => 'json-api-models',
            'attributes' => [
                'name' => 'Manager',
                'email' => 'deputy@example.com',
            ],
        ], $responseData['included'][0]);
    }

    protected function fakeJsonApiResponseForModel(Model $model): array
    {
        Container::getInstance()->instance(ResponseFactoryContract::class, new ResponseFactory(
            m::mock(ViewFactory::class),
            m::mock(Redirector::class)
        ));

        return (new JsonApiResource($model))
            ->toResponse(new Request)
            ->getData(true);
    }
}

class JsonApiModel extends Model
{
    protected $guarded = [];
}
