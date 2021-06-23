<?php


namespace Illuminate\Http\Client\CacheHandlers;


use BadMethodCallException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Http\Client\CacheHandler as CacheHandlerContract;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CacheHandler implements CacheHandlerContract
{
    const CACHE_PREFIX = 'http-client::';

    /**
     * The cache to use when storing the response.
     *
     * @var Repository
     */
    protected $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Determine whether or not a cached response exists for the given request.
     *
     * @return bool
     */
    public function hasCachedResponse(Request $request)
    {
        return $this->cache->has($this->getCacheKey($request));
    }

    /**
     * Retrieve the cache key to be used based on the given cache options.
     *
     * @return string
     */
    protected function getCacheKey(Request $request)
    {
        return static::CACHE_PREFIX
            . $request->url()
            . (($key = $request->cacheOptions()->getKey()) ? "::$key" : '');
    }

    /**
     * Return the cached response for the given request.
     *
     * @return Response|null $response
     */
    public function getCachedResponse(Request $request)
    {
        return $this->cache->get($this->getCacheKey($request));
    }

    /**
     * Cache the response if possible.
     *
     * @return bool
     */
    public function handleCaching(Request $request, Response $response)
    {
        return $this->canCache($request, $response)
            ? $this->cache->put($this->getCacheKey($request), $response, $this->getCacheExpiry($request, $response))
            : false;
    }

    /**
     * Determine if a response is allowed to be cached.
     *
     * @return bool
     *
     * @throws \BadMethodCallException
     */
    protected function canCache(Request $request, Response $response)
    {
        $cacheControlHeader = $this->parseHeaderParts($response->header('Cache-Control'));

        if ($cacheControlHeader->has('private') && !$request->cacheOptions()->getKey()) {
            throw new BadMethodCallException('You cannot cache a request marked as private without providing a key.');
        }

        return $this->getCacheExpiry($request, $response) && !$cacheControlHeader->has('no-store');
    }

    /**
     * Retrieve the time-to-live in seconds based on the given cache options or request headers.
     *
     * @return int|null
     */
    protected function getCacheExpiry(Request $request, Response $response)
    {
        $cacheControlItems = $this->parseHeaderParts($response->header('Cache-Control'));

        return $request->cacheOptions()->getExpiry()
            ?? $cacheControlItems->get('s-maxage')
            ?? $cacheControlItems->get('max-age')
            ?? (($date = $response->header('Expires')) ? Carbon::parse($date)->diffInRealSeconds() : null);
    }

    /**
     * Parse a header into separated keys and values.
     *
     * @return Collection
     */
    protected function parseHeaderParts(string $header)
    {
        return collect(explode(',', $header))->mapWithKeys(function ($option) {
            $data = explode('=', $option, 2);

            return [$data[0] => $data[1] ?? true];
        });
    }
}
