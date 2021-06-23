<?php


namespace Illuminate\Contracts\Http\Client;


use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

interface CacheHandler
{

    /**
     * Determine whether or not a cached response exists for the given request.
     *
     * @return bool
     */
    public function hasCachedResponse(Request $request);

    /**
     * Return the cached response for the given request.
     *
     * @return \Illuminate\Http\Client\Response|null $response
     */
    public function getCachedResponse(Request $request);

    /**
     * Cache the response if possible.
     *
     * @return bool
     */
    public function handleCaching(Request $request, Response $response);

}
