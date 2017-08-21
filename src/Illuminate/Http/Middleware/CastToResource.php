<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Contracts\Database\CastsToResource;

class CastToResource
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (! isset($response->original)) {
            return $response;
        }

        if ($response->original instanceof Model &&
            $response->original instanceof CastsToResource) {
            return $this->castModelToResource($request, $response);
        }

        if ($response->original instanceof Collection &&
            $response->original->first() instanceof CastsToResource) {
            return $this->castCollectionToResource($request, $response);
        }

        if ($response->original instanceof AbstractPaginator &&
            $response->original->getCollection()->first() instanceof CastsToResource) {
            return $this->castPaginatorToResource($request, $response);
        }

        return $response;
    }

    /**
     * Cast the model sent within the response to a resource response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    protected function castModelToResource($request, $response)
    {
        return $response->original->castToResource(
            $request, $response->original
        )->toResponse($request);
    }

    /**
     * Cast the collection sent within the response to a resource response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    protected function castCollectionToResource($request, $response)
    {
        return $response->original->first()->castCollectionToResource(
            $request, $response->original
        )->toResponse($request);
    }

    /**
     * Cast the collection sent within the response to a resource response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    protected function castPaginatorToResource($request, $response)
    {
        $collection = $response->original->getCollection();

        return $collection->first()->castCollectionToResource(
            $request, $response->original
        )->toResponse($request);
    }
}
