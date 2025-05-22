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
        $paginationInformation = $this->paginationInformation($request);

        return tap(response()->json(
            $this->wrap(
                $this->resource->resolve($request),
                array_merge_recursive(
                    $this->paginationEnabled() ? $paginationInformation : [],
                    $this->resource->with($request),
                    $this->resource->additional
                )
            ),
            $this->calculateStatus(),
            $this->paginationHeadersEnabled() ? $this->responseHeaders($paginationInformation) : [],
            $this->resource->jsonOptions()
        ), function ($response) use ($request) {
            $response->original = $this->resource->resource->map(function ($item) {
                return is_array($item) ? Arr::get($item, 'resource') : optional($item)->resource;
            });

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

        $default = [
            'links' => $this->paginationLinks($paginated),
            'meta' => $this->meta($paginated),
        ];

        if (method_exists($this->resource, 'paginationInformation') ||
            $this->resource->hasMacro('paginationInformation')) {
            return $this->resource->paginationInformation($request, $paginated, $default);
        }

        return $default;
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

    /**
     * Get the response headers for the given pagination information.
     *
     * @param  array  $pagination
     * @return array
     */
    protected function responseHeaders($pagination)
    {
        return array_filter([
            'X-Pagination-Current-Page' => $pagination['meta']['current_page'] ?? null,
            'X-Pagination-From' => $pagination['meta']['from'] ?? null,
            'X-Pagination-Last-Page' => $pagination['meta']['last_page'] ?? null,
            'X-Pagination-Path' => $pagination['meta']['path'] ?? null,
            'X-Pagination-Per-Page' => $pagination['meta']['per_page'] ?? null,
            'X-Pagination-To' => $pagination['meta']['to'] ?? null,
            'X-Pagination-Total' => $pagination['meta']['total'] ?? null,
            'X-Pagination-Links-First' => $pagination['links']['first'] ?? null,
            'X-Pagination-Links-Last' => $pagination['links']['last'] ?? null,
            'X-Pagination-Links-Prev' => $pagination['links']['prev'] ?? null,
            'X-Pagination-Links-Next' => $pagination['links']['next'] ?? null,
        ]);
    }
}
