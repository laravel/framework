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
        $collection = new TestCollection([
            new TestModel(),
        ]);
        $this->assertEquals('App\Http\Resources\TestModelResource', $collection->getGuessedResourceName(new TestModel()));
    }

    public function testItCanTransformToExplicitResource()
    {
        $collection = new Collection([
            new TestModel(),
        ]);

        $resource = $collection->toResourceCollection(TestResource::class);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Database\TestModel].');

        $collection = new Collection([
            new TestModel(),
        ]);
        $collection->toResourceCollection();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $collection = new Collection([
            new TestModel(),
        ]);

        class_alias(TestResource::class, 'App\Http\Resources\TestModelResource');

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }
}

class TestModel extends Model
{
    //
}

class TestResource extends JsonResource
{
    //
}

class TestCollection extends Collection
{
    public function getGuessedResourceName(object $model): string
    {
        return $this->guessResourceClassName($model);
    }
}
