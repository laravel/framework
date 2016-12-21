<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;

class LocalizeRequest
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new Middleware instance.
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
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $guessedLocale = $request->segment(1);

        $locale = $this->app->hasLocale($guessedLocale) ?
                        $guessedLocale : $this->app->getFallbackLocale();

        $request->setLocale($locale);

        $this->app->setLocale($locale);

        return $next($request);
    }
}
