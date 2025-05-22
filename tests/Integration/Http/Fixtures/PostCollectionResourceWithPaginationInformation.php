<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollectionResourceWithPaginationInformation extends ResourceCollection
{
    public $collects = PostResource::class;

    public function toArray($request)
    {
        return ['data' => $this->collection];
    }

    public function paginationInformation($request)
    {
        $paginated = $this->resource->toArray();

        return [
            'current_page' => $paginated['current_page'],
            'per_page' => $paginated['per_page'],
            'total' => $paginated['total'],
            'total_page' => $paginated['last_page'],
        ];
    }

    public function responseHeaders($request, $pagination, $default)
    {
        return array_filter([
            'X-Pagination-Current-Page' => $pagination['current_page'] ?? null,
            'X-Pagination-Per-Page' => $pagination['per_page'] ?? null,
            'X-Pagination-Total' => $pagination['total'] ?? null,
            'X-Pagination-Total-Page' => $pagination['total_page'] ?? null,
        ]);
    }
}
