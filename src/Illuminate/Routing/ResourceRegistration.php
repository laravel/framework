<?php

namespace Illuminate\Routing;

class ResourceRegistration
{
    /**
     * The resource.
     *
     * @var array
     */
    protected $resource;

    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults;

    /**
     * The route collection instance.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * Create a new pending resource registration instance.
     *
     * @param  array  $resource
     * @param  array  $resourceDefaults
     * @param  \Illuminate\Routing\RouteCollection  $router
     * @return void
     */
    public function __construct(array $resource, array $resourceDefaults, RouteCollection $routes)
    {
        $this->resource = $resource;
        $this->resourceDefaults = $resourceDefaults;
        $this->routes = $routes;
    }

    /**
     * Set the methods the controller should apply to.
     *
     * @param  array|string|dynamic  $methods
     */
    public function only($methods)
    {
        $this->resource['options']['only'] = is_array($methods) ? $methods : func_get_args();

        $this->removeRoutes($this->limitedMethods());
    }

    /**
     * Set the methods the controller should exclude.
     *
     * @param  array|string|dynamic  $methods
     */
    public function except($methods)
    {
        $this->resource['options']['except'] = is_array($methods) ? $methods : func_get_args();

        $this->removeRoutes($this->excludedMethods());
    }

    /**
     * Get the excluded route methods.
     *
     * @return array
     */
    protected function excludedMethods()
    {
        return array_intersect(
            $this->resourceDefaults,
            $this->resource['options']['except']
        );
    }

    /**
     * Get the route methods the controller shouldn't apply to.
     *
     * @return array
     */
    protected function limitedMethods()
    {
        return array_diff(
            $this->resourceDefaults,
            $this->resource['options']['only']
        );
    }

    /**
     * Remove methods from the routes.
     *
     * @param  array  $methods
     * @return void
     */
    protected function removeRoutes(array $methods)
    {
        foreach ($methods as $method) {
            $name = $this->resource['name'].'.'.$method;

            if ($route = $this->routes->getByName($name)) {
                $this->routes->remove($route);
            }
        }
    }
}
