<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Tests\Pagination\Fixtures\Models\PaginatorResourceTestModel;
use PHPUnit\Framework\TestCase;

class CursorResourceTest extends TestCase
{
    public function testItCanTransformToExplicitResource()
    {
        $paginator = new CursorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1);

        $resource = $paginator->toResourceCollection(CursorResourceTestResource::class);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Pagination\Fixtures\Models\PaginatorResourceTestModel].');

        $paginator = new CursorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1);

        $paginator->toResourceCollection();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $paginator = new CursorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1);

        class_alias(CursorResourceTestResource::class, 'Illuminate\Tests\Pagination\Fixtures\Http\Resources\PaginatorResourceTestModelResource');

        $resource = $paginator->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }
}

class CursorResourceTestResource extends JsonResource
{
    //
}

class CursorResourceTestPaginator extends CursorPaginator
{
    //
}
