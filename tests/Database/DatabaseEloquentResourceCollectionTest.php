<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentResourceCollectionTest extends TestCase
{
    public function testItGuessesTheResourceName()
    {
        $collection = new EloquentResourceCollectionTestCollection([
            new EloquentResourceCollectionTestModel(),
        ]);
        $this->assertEquals('App\Http\Resources\EloquentResourceCollectionTestModelResource', $collection->getGuessedResourceName(new EloquentResourceCollectionTestModel()));
    }

    public function testItCanTransformToExplicitResource()
    {
        $collection = new Collection([
            new EloquentResourceCollectionTestModel(),
        ]);

        $resource = $collection->toResourceCollection(EloquentResourceCollectionTestResource::class);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Database\EloquentResourceCollectionTestModel].');

        $collection = new Collection([
            new EloquentResourceCollectionTestModel(),
        ]);
        $collection->toResourceCollection();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $collection = new Collection([
            new EloquentResourceCollectionTestModel(),
        ]);

        class_alias(EloquentResourceCollectionTestResource::class, 'App\Http\Resources\EloquentResourceCollectionTestModelResource');

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }
}

class EloquentResourceCollectionTestModel extends Model
{
    //
}

class EloquentResourceCollectionTestResource extends JsonResource
{
    //
}

class EloquentResourceCollectionTestCollection extends Collection
{
    public function getGuessedResourceName(object $model): string
    {
        return $this->guessResourceClassName($model);
    }
}
