<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentResourceModelTest extends TestCase
{
    public function testItCanTransformToExplicitResource()
    {
        $model = new TestResourceModel();
        $resource = $model->toResource(TestJsonResource::class);

        $this->assertInstanceOf(TestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Database\TestResourceModel].');

        $model = new TestResourceModel();
        $model->toResource();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $model = new TestResourceModelWithGuessableResource();

        class_alias(TestJsonResource::class, 'App\Http\Resources\TestResourceModelWithGuessableResourceResource');

        $resource = $model->toResource();

        $this->assertInstanceOf(TestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }

    public function testItCanGuessResourceName()
    {
        $model = new TestResourceModel();
        $this->assertEquals('App\Http\Resources\TestResourceModelResource', $model->getGuessedResourceName());
    }

}

class TestResourceModel extends Model
{
    public function getGuessedResourceName(): string
    {
        return $this->guessResourceName();
    }
}

class TestResourceModelWithGuessableResource extends Model
{
}

class TestJsonResource extends JsonResource
{
}
