<?php

namespace Illuminate\Routing;

class ResourceRegistration
{
    /**
     * The resource name.
     *
     * @var string
     */
    protected $name;

    /**
     * The resource controller.
     *
     * @var string
     */
    protected $controller;

    /**
     * The resource options.
     *
     * @var string
     */
    protected $options = [];

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
     * @param  array  $resourceDefaults
     * @param  \Illuminate\Routing\RouteCollection  $router
     * @return void
     */
    public function __construct(array $resourceDefaults, RouteCollection $routes)
    {
        $this->resourceDefaults = $resourceDefaults;
        $this->routes = $routes;
    }

    /**
     * The name, controller and options to use when ready to register.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    public function remember($name, $controller, array $options)
    {
        $this->name = $name;
        $this->controller = $controller;
        $this->options = $options;
    }

    /**
     * Set the methods the controller should apply to.
     *
     * @param  array|string|dynamic  $methods
     */
    public function only($methods)
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();

        $this->removeRoutes(
            array_diff($this->resourceDefaults, $this->options['only'])
        );
    }

    /**
     * Set the methods the controller should exclude.
     *
     * @param  array|string|dynamic  $methods
     */
    public function except($methods)
    {
        $this->options['except'] = is_array($methods) ? $methods : func_get_args();

        $this->removeRoutes(
            array_intersect($this->resourceDefaults, $this->options['except'])
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
            $name = $this->name.'.'.$method;

            if ($route = $this->routes->getByName($name)) {
                $this->routes->remove($route);
            }
        }
    }
}
