<?php

namespace Illuminate\Http\Resources;

use Illuminate\Support\Arr;

class PaginatedJsonResourceResponse extends JsonResourceResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        $this->addPaginationInformation($request);

        return $this->build($request, response()->json(
            array_merge($this->wrap($this->resource->toJson($request)), $this->with),
            $this->calculateStatus(), $this->headers
        ));
    }

    /**
     * Add the pagination information to the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function addPaginationInformation($request)
    {
        $paginated = $this->resource->resource->toArray();

        return $this->with([
            'links' => $this->paginationLinks($paginated),
            'meta' => $this->meta($paginated),
        ]);
    }

    /**
     * Get the pagination links for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    protected function paginationLinks($paginated)
    {
        return array_merge($this->with['link'] ?? [], [
            'first' => $paginated['first_page_url'] ?? null,
            'last' => $paginated['last_page_url'] ?? null,
            'prev' => $paginated['prev_page_url'] ?? null,
            'next' => $paginated['next_page_url'] ?? null,
        ]);
    }

    /**
     * Gather the meta data for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    protected function meta($paginated)
    {
        return array_merge($this->with['meta'] ?? [], Arr::except($paginated, [
            'data',
            'first_page_url',
            'last_page_url',
            'prev_page_url',
            'next_page_url',
        ]));
    }
}
