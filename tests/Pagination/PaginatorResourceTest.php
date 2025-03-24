<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

class PaginatorResourceTest extends TestCase
{
    public function testItGuessesTheResourceName()
    {
        $paginator = new TestPaginator([
            new TestModel(),
        ], 1, 1);

        $this->assertEquals('App\Http\Resources\TestModelResource', $paginator->getGuessedResourceName(new TestModel()));
    }

    public function testItCanTransformToExplicitResource()
    {
        $paginator = new TestPaginator([
            new TestModel(),
        ], 1, 1);

        $resource = $paginator->toResourceCollection(TestResource::class);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Pagination\TestModel].');

        $paginator = new TestPaginator([
            new TestModel(),
        ], 1, 1);

        $paginator->toResourceCollection();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $paginator = new TestPaginator([
            new TestModel(),
        ], 1, 1);

        class_alias(TestResource::class, 'App\Http\Resources\TestModelResource');

        $resource = $paginator->toResourceCollection();

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

class TestPaginator extends LengthAwarePaginator
{
    public function getGuessedResourceName(object $model): string
    {
        return $this->guessResourceClassName($model);
    }
}
