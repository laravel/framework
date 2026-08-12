<?php

namespace Illuminate\Http\Middleware;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Vite;

class AddLinkHeadersForPreloadedAssets
{
    /**
     * Configure the middleware.
     *
     * @param  int  $limit
     * @return string
     */
    public static function using($limit)
    {
        return static::class.':'.$limit;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|null  $limit
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next, $limit = null)
    {
        return tap($next($request), static function ($response) use ($limit) {
            if ($response instanceof Response && Vite::preloadedAssets() !== []) {
                $response->header('Link', (new Collection(Vite::preloadedAssets()))
                    ->when($limit, static fn ($assets, $limit) => $assets->take($limit))
                    ->map(static fn ($attributes, $url) => "<{$url}>; ".implode('; ', $attributes))
                    ->join(', '), false);
            }
        });
    }
}
