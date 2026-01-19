<?php

namespace Illuminate\Tests\Database;

use Illuminate\Tests\Database\Fixtures\Models\EloquentResourceTestResourceModel;
use Illuminate\Tests\Database\Fixtures\Models\EloquentResourceTestResourceModelWithGuessableResource;
use Illuminate\Tests\Database\Fixtures\Models\EloquentResourceTestResourceModelWithUseResourceAttribute;
use Illuminate\Tests\Database\Fixtures\Resources\EloquentResourceTestJsonResource;
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
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Database\Fixtures\Models\EloquentResourceTestResourceModel].');

        $model = new EloquentResourceTestResourceModel();
        $model->toResource();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $model = new EloquentResourceTestResourceModelWithGuessableResource();

        class_alias(EloquentResourceTestJsonResource::class, 'Illuminate\Tests\Database\Fixtures\Http\Resources\EloquentResourceTestResourceModelWithGuessableResourceResource');

        $resource = $model->toResource();

        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }

    public function testItCanGuessResourceWhenNotProvidedWithNonResourceSuffix()
    {
        $model = new EloquentResourceTestResourceModelWithGuessableResource();

        class_alias(EloquentResourceTestJsonResource::class, 'Illuminate\Tests\Database\Fixtures\Http\Resources\EloquentResourceTestResourceModelWithGuessableResource');

        $resource = $model->toResource();

        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }

    public function testItCanGuessResourceName()
    {
        $model = new EloquentResourceTestResourceModel();
        $this->assertEquals([
            'Illuminate\Tests\Database\Fixtures\Http\Resources\EloquentResourceTestResourceModelResource',
            'Illuminate\Tests\Database\Fixtures\Http\Resources\EloquentResourceTestResourceModel',
        ], $model::guessResourceName());
    }

    public function testItCanTransformToResourceViaUseResourceAttribute()
    {
        $model = new EloquentResourceTestResourceModelWithUseResourceAttribute();

        $resource = $model->toResource();

        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }
}
