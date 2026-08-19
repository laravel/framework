<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Tests\App\Http\Resources\EloquentResourceCollectionTestResource;
use Illuminate\Tests\App\Http\Resources\EloquentResourceTestJsonResource;
use Illuminate\Tests\App\Http\Resources\EloquentResourceTestJsonResourceCollection;
use Illuminate\Tests\App\Models\EloquentResourceCollectionTestModel;
use Illuminate\Tests\App\Models\EloquentResourceTestResourceModelWithUseResourceAttribute;
use Illuminate\Tests\App\Models\EloquentResourceTestResourceModelWithUseResourceCollectionAttribute;
use LogicException;
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
        $this->expectExceptionObject(new LogicException('Failed to find resource class for model [Illuminate\Tests\App\Models\EloquentResourceCollectionTestModel].'));

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

        class_alias(EloquentResourceCollectionTestResource::class, 'Illuminate\Tests\App\Http\Resources\EloquentResourceCollectionTestModelResource');

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItCanTransformToResourceViaUseResourceAttribute()
    {
        $collection = new Collection([
            new EloquentResourceTestResourceModelWithUseResourceCollectionAttribute(),
        ]);

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(EloquentResourceTestJsonResourceCollection::class, $resource);
    }

    public function testItCanTransformToResourceViaUseResourceCollectionAttribute()
    {
        $collection = new Collection([
            new EloquentResourceTestResourceModelWithUseResourceAttribute(),
        ]);

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(AnonymousResourceCollection::class, $resource);
        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource[0]);
    }
}
