<?php

namespace Illuminate\Auth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;

class EnsureEmailIsVerified
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The response factory instance.
     *
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    protected $responseFactory;

    /**
     * The URL generator instance.
     *
     * @var \Illuminate\Contracts\Routing\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $responseFactory
     * @param  \Illuminate\Contracts\Routing\UrlGenerator  $urlGenerator
     * @return void
     */
    public function __construct(Application $app, ResponseFactory $responseFactory, UrlGenerator $urlGenerator)
    {
        $this->app = $app;
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Specify the redirect route for the middleware.
     *
     * @param  string  $route
     * @return string
     */
    public static function redirectTo($route)
    {
        return static::class.':'.$route;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! $request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail())) {
            return $request->expectsJson()
                    ? $this->app->abort(403, 'Your email address is not verified.')
                    : $this->responseFactory->redirectGuest(
                        $this->urlGenerator->route($redirectToRoute ?: 'verification.notice')
                    );
        }

        return $next($request);
    }
}
