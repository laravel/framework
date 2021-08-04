<?php declare(strict_types=1);

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\PaginatedResourceResponse;
use Illuminate\Pagination\AbstractCursorPaginator;

class PostResourcePaginatedResponse extends PaginatedResourceResponse
{
    /**
     * Add the pagination information to the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function paginationInformation($request)
    {
        /** @var AbstractCursorPaginator $paginator */
        $paginator = $this->resource;

        return [
            'cursor' => $paginator->cursor()->encode(),
        ];
    }
}
