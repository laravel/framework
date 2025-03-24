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
        $paginator = new PaginatorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1, 1, 1);

        $this->assertEquals('App\Http\Resources\PaginatorResourceTestModelResource', $paginator->getGuessedResourceName(new PaginatorResourceTestModel()));
    }

    public function testItCanTransformToExplicitResource()
    {
        $paginator = new PaginatorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1, 1, 1);

        $resource = $paginator->toResourceCollection(PaginatorResourceTestResource::class);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Pagination\PaginatorResourceTestModel].');

        $paginator = new PaginatorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1, 1, 1);

        $paginator->toResourceCollection();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $paginator = new PaginatorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1, 1, 1);

        class_alias(PaginatorResourceTestResource::class, 'App\Http\Resources\PaginatorResourceTestModelResource');

        $resource = $paginator->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }
}

class PaginatorResourceTestModel extends Model
{
    //
}

class PaginatorResourceTestResource extends JsonResource
{
    //
}

class PaginatorResourceTestPaginator extends LengthAwarePaginator
{
    public function getGuessedResourceName(object $model): string
    {
        return $this->guessResourceClassName($model);
    }
}
