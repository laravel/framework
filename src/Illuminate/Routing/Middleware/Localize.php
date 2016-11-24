<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;

class Localize
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The URL generator instance.
     *
     * @var \Illuminate\Contracts\Routing\UrlGenerator
     */
    protected $url;

    /**
     * Create a new request localizer.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Routing\UrlGenerator  $url
     * @return void
     */
    public function __construct(Application $app, UrlGenerator $url)
    {
        $this->app = $app;
        $this->url = $url;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $defaultLocale = $this->app['config']->get('app.fallback_locale');

        if (! $this->requestPathHasLocale($request)) {
            return redirect(trim($defaultLocale.'/'.$request->path(), '/'));
        }

        $locale = $this->getLocaleFromRequest($request);

        $this->app->setLocale($locale);

        $this->url->formatPathUsing(function ($path) use ($locale) {
            return rtrim('/'.$locale.$path, '/');
        });

        return $next($request);
    }

    /**
     * Determine if the request path contains locale information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function requestPathHasLocale($request)
    {
        return in_array($request->segment(1), $this->app['config']->get('app.locales'));
    }

    /**
     * Extract the locale from the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function getLocaleFromRequest($request)
    {
        return $this->requestPathHasLocale($request) ?
                        $request->segment(1) : $this->app['config']->get('app.fallback_locale');
    }
}
