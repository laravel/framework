<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Config\Repository;

class RoutesLocalizer
{
    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The configuration repository instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $config;

    /**
     * Create a new routes localizer instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(Router $router, Repository $config)
    {
        $this->router = $router;
        $this->config = $config;
    }

    /**
     * Localize routes using the given locales.
     *
     * @return void
     */
    public function localize()
    {
        foreach ($this->router->getRoutes() as $route) {
            if (! $this->routeShouldBeLocalized($route)) {
                return;
            }

            foreach ($this->config->get('app.locales') as $locale) {
                $this->addRouteForLocale($route, $locale);
            }
        }
    }

    /**
     * Add a route for the locale in the route collection.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $locale
     * @return void
     */
    private function addRouteForLocale($route, $locale)
    {
        $route = clone $route;

        $routeUri = $route->getUri();

        $route->setUri(
            $routeUri == '/' ? $locale : $locale.'/'.$routeUri
        );

        if ($route->getName()) {
            $route->name('.'.$locale);
        }

        $this->router->getRoutes()->add($route);
    }

    /**
     * Determine if the route should be localized.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return bool
     */
    private function routeShouldBeLocalized($route)
    {
        return in_array('localize', $route->gatherMiddleware());
    }
}
