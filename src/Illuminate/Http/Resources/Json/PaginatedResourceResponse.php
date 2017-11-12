<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginatedResourceResponse extends ResourceResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return tap(response()->json(
            $this->wrap(
                $this->resource->resolve($request),
                array_merge_recursive(
                    $this->paginationInformation($request),
                    $this->resource->with($request),
                    $this->resource->additional
                )
            ),
            $this->calculateStatus()
        ), function ($response) use ($request) {
            $this->resource->withResponse($request, $response);
        });
    }

    /**
     * Add the pagination information to the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function paginationInformation($request)
    {
        $paginator = $this->resource->resource;

        return [
            'links' => $this->paginationLinks($paginator),
            'meta' => $this->meta($paginator),
        ];
    }

    /**
     * Get the pagination links for the response.
     *
     * @param  \Illuminate\Pagination\AbstractPaginator  $paginator
     * @return array
     */
    protected function paginationLinks($paginator)
    {
        $links = [
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl()
        ];

        if ($this->isLengthAware()) {
            $links += [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage())
            ];
        }

        return $links;
    }

    /**
     * Gather the meta data for the response.
     *
     * @param  \Illuminate\Pagination\AbstractPaginator  $paginator
     * @return array
     */
    protected function meta($paginator)
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'path' => $paginator->path,
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem()
        ];

        if ($this->isLengthAware()) {
            $meta += [
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total()
            ];
        }

        return $meta;
    }

    /**
     * Is it a LengthAwarePaginator.
     *
     * @return bool
     */
    protected function isLengthAware()
    {
        return $this->resource->resource instanceof LengthAwarePaginator;
    }
}
