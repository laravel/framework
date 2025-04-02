<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Tests\Database\Fixtures\Models\EloquentResourceCollectionTestModel;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentResourceCollectionTest extends TestCase
{
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
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Database\Fixtures\Models\EloquentResourceCollectionTestModel].');

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

        class_alias(EloquentResourceCollectionTestResource::class, 'Illuminate\Tests\Database\Fixtures\Http\Resources\EloquentResourceCollectionTestModelResource');

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }
}

class EloquentResourceCollectionTestResource extends JsonResource
{
    //
}
