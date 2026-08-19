<?php

namespace Illuminate\Tests\Database;

use Illuminate\Tests\App\Http\Resources\EloquentResourceTestJsonResource;
use Illuminate\Tests\App\Models\EloquentResourceTestResourceModel;
use Illuminate\Tests\App\Models\EloquentResourceTestResourceModelWithGuessableResource;
use Illuminate\Tests\App\Models\EloquentResourceTestResourceModelWithUseResourceAttribute;
use LogicException;
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
        $this->expectExceptionObject(new LogicException('Failed to find resource class for model [Illuminate\Tests\App\Models\EloquentResourceTestResourceModel].'));

        $model = new EloquentResourceTestResourceModel();
        $model->toResource();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $model = new EloquentResourceTestResourceModelWithGuessableResource();

        class_alias(EloquentResourceTestJsonResource::class, 'Illuminate\Tests\App\Http\Resources\EloquentResourceTestResourceModelWithGuessableResourceResource');

        $resource = $model->toResource();

        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }

    public function testItCanGuessResourceWhenNotProvidedWithNonResourceSuffix()
    {
        $model = new EloquentResourceTestResourceModelWithGuessableResource();

        class_alias(EloquentResourceTestJsonResource::class, 'Illuminate\Tests\App\Http\Resources\EloquentResourceTestResourceModelWithGuessableResource');

        $resource = $model->toResource();

        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource);
        $this->assertSame($model, $resource->resource);
    }

    public function testItCanGuessResourceName()
    {
        $model = new EloquentResourceTestResourceModel();
        $this->assertEquals([
            'Illuminate\Tests\App\Http\Resources\EloquentResourceTestResourceModelResource',
            'Illuminate\Tests\App\Http\Resources\EloquentResourceTestResourceModel',
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
