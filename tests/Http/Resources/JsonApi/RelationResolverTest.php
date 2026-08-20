<?php

namespace Illuminate\Tests\Http\Resources\JsonApi;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\JsonApi\RelationResolver;
use Illuminate\Tests\App\Http\Resources\JsonApi\RelationResolvingResource;
use Illuminate\Tests\App\Models\RelationResolvingModel;
use PHPUnit\Framework\TestCase;

class RelationResolverTest extends TestCase
{
    public function testResolvesClosureReturningResourceCollection()
    {
        $first = new RelationResolvingModel(['id' => 1]);
        $second = new RelationResolvingModel(['id' => 2]);

        $resolver = new RelationResolver('comments', fn () => RelationResolvingResource::collection([$first, $second]));

        $resolved = $resolver->handle(new RelationResolvingModel);

        $this->assertInstanceOf(EloquentCollection::class, $resolved);
        $this->assertSame([$first, $second], $resolved->all());
        $this->assertSame(RelationResolvingResource::class, $resolver->resourceClass());
    }

    public function testResolvesClosureReturningSingleResource()
    {
        $model = new RelationResolvingModel(['id' => 1]);

        $resolver = new RelationResolver('author', fn () => new RelationResolvingResource($model));

        $resolved = $resolver->handle(new RelationResolvingModel);

        $this->assertSame($model, $resolved);
        $this->assertSame(RelationResolvingResource::class, $resolver->resourceClass());
    }

    public function testResolvesClosureReturningRawModels()
    {
        $model = new RelationResolvingModel(['id' => 1]);

        $resolver = new RelationResolver('comments', fn () => new EloquentCollection([$model]));

        $resolved = $resolver->handle(new RelationResolvingModel);

        $this->assertInstanceOf(EloquentCollection::class, $resolved);
        $this->assertSame([$model], $resolved->all());
        $this->assertNull($resolver->resourceClass());
    }

    public function testResolvesClosureReturningNull()
    {
        $resolver = new RelationResolver('author', fn () => null);

        $this->assertNull($resolver->handle(new RelationResolvingModel));
        $this->assertNull($resolver->resourceClass());
    }
}
