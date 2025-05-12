<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Tests\Pagination\Fixtures\Models\PaginatorResourceTestModel;
use PHPUnit\Framework\TestCase;

class PaginatorResourceTest extends TestCase
{
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
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to find resource class for model [Illuminate\Tests\Pagination\Fixtures\Models\PaginatorResourceTestModel].');

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

        class_alias(PaginatorResourceTestResource::class, 'Illuminate\Tests\Pagination\Fixtures\Http\Resources\PaginatorResourceTestModelResource');

        $resource = $paginator->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }
}

class PaginatorResourceTestResource extends JsonResource
{
    //
}

class PaginatorResourceTestPaginator extends LengthAwarePaginator
{
    //
}
