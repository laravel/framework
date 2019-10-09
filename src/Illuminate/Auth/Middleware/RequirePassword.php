<?php

namespace Illuminate\Auth\Middleware;

use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;

class RequirePassword
{
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
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $responseFactory
     * @param  \Illuminate\Contracts\Routing\UrlGenerator  $urlGenerator
     * @return void
     */
    public function __construct(ResponseFactory $responseFactory, UrlGenerator $urlGenerator)
    {
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $redirectToRoute
     * @return mixed
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if ($this->shouldConfirmPassword($request)) {
            return $this->responseFactory->redirectGuest(
                $this->urlGenerator->route($redirectToRoute ?? 'password.confirm')
            );
        }

        return $next($request);
    }

    /**
     * Determine if the confirmation timeout has expired.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldConfirmPassword($request)
    {
        $confirmedAt = time() - $request->session()->get('auth.password_confirmed_at', 0);

        return $confirmedAt > config('auth.password_timeout', 10800);
    }
}
