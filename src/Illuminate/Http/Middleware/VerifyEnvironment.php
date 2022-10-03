<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;

class VerifyEnvironment
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$environments
     * @return mixed
     */
    public function handle($request, Closure $next, ...$environments)
    {
        if (! in_array($this->app->environment(), $environments, true)) {
            return response(status: 404);
        }

        return $next($request);
    }
}
