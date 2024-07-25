<?php

namespace Illuminate\Auth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

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
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The callback that should be used to generate the authentication redirect path.
     *
     * @var callable|null
     */
    protected static $redirectToCallback;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $responseFactory
     * @param  \Illuminate\Contracts\Routing\UrlGenerator  $urlGenerator
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Auth $auth, ResponseFactory $responseFactory, UrlGenerator $urlGenerator, Router $router)
    {
        $this->auth = $auth;
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
        $this->router = $router;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->responseFactory->redirectTo($this->redirectTo($request));
            }
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return static::$redirectToCallback
            ? call_user_func(static::$redirectToCallback, $request)
            : $this->defaultRedirectUri();
    }

    /**
     * Get the default URI the user should be redirected to when they are authenticated.
     */
    protected function defaultRedirectUri(): string
    {
        foreach (['dashboard', 'home'] as $routeName) {
            if ($this->router->has($routeName)) {
                return $this->urlGenerator->route($routeName);
            }
        }

        $routes = $this->router->getRoutes()->get('GET');

        foreach (['dashboard', 'home'] as $uri) {
            if (isset($routes[$uri])) {
                return $this->urlGenerator->to('/'.$uri);
            }
        }

        return '/';
    }

    /**
     * Specify the callback that should be used to generate the redirect path.
     *
     * @param  callable  $redirectToCallback
     * @return void
     */
    public static function redirectUsing(callable $redirectToCallback)
    {
        static::$redirectToCallback = $redirectToCallback;
    }
}
