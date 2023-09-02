<?php

namespace Illuminate\Cache\Contracts;

interface RateLimit
{
    /**
     * Resolve rate limit for the given request or job.
     *
     * @param  \Illuminate\Http\Request|mixed  $request
     * @return \Illuminate\Cache\RateLimiting\Limit|array<int, \Illuminate\Cache\RateLimiting\Limit>
     */
    public function __invoke($request);
}
