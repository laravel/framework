<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Support\Arr;

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
        $paginated = $this->resource->resource->toArray();

        return [
            'links' => $this->paginationLinks($paginated),
            'meta' => $this->meta($paginated),
        ];
    }

    /**
     * Get the pagination links for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    protected function paginationLinks($paginated)
    {
        return [
            'first' => $paginated['first_page_url'] ?? null,
            'last' => $paginated['last_page_url'] ?? null,
            'prev' => $paginated['prev_page_url'] ?? null,
            'next' => $paginated['next_page_url'] ?? null,
        ];
    }

    /**
     * Gather the meta data for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    protected function meta($paginated)
    {
        return Arr::except($paginated, [
            'data',
            'first_page_url',
            'last_page_url',
            'prev_page_url',
            'next_page_url',
        ]);
    }
}
