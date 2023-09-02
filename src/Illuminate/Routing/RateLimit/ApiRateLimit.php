<?php

namespace Illuminate\Routing\RateLimit;

use Illuminate\Cache\Contracts\RateLimit;
use Illuminate\Cache\RateLimiting\Limit;

class ApiRateLimit implements RateLimit
{
    public function __invoke($request)
    {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    }
}
