<?php

namespace Illuminate\Routing;

class RouteFileRegistrar
{
    /**
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * RegisterRouteFile constructor.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register the route file.
     *
     * @param  $routes
     * @return void
     */
    public function register($routes)
    {
        $router = $this->router;

        require $routes;
    }
}
