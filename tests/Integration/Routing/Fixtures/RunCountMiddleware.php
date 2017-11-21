<?php

namespace Illuminate\Tests\Integration\Routing\Fixtures;

use Closure;

class RunCountMiddleware
{
    public static $runCount = 0;

    public function handle($request, Closure $next)
    {
        static::$runCount += 1;

        return $next($request);
    }
}
