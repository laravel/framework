<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentResourceModelTest extends TestCase
{
    public function testItCanTransformToExplicitResource()
    {
        $model = new EloquentResourceTestResourceModel();
        $resource = $model->toResource(EloquentResourceTestJsonResource::class);

        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Database\EloquentResourceTestResourceModel].');

        $model = new EloquentResourceTestResourceModel();
        $model->toResource();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $model = new EloquentResourceTestResourceModelWithGuessableResource();

        class_alias(EloquentResourceTestJsonResource::class, 'App\Http\Resources\EloquentResourceTestResourceModelWithGuessableResourceResource');

        $resource = $model->toResource();

        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }

    public function testItCanGuessResourceName()
    {
        $model = new EloquentResourceTestResourceModel();
        $this->assertEquals('App\Http\Resources\EloquentResourceTestResourceModelResource', $model->getGuessedResourceName());
    }
}

class EloquentResourceTestResourceModel extends Model
{
    public function getGuessedResourceName(): string
    {
        return $this->guessResourceName();
    }
}

class EloquentResourceTestResourceModelWithGuessableResource extends Model
{
    //
}

class EloquentResourceTestJsonResource extends JsonResource
{
    //
}
