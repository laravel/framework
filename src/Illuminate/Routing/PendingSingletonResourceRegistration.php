<?php

namespace Illuminate\Routing;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;

class PendingSingletonResourceRegistration
{
    use CreatesRegularExpressionRouteConstraints, Macroable;

    /**
     * The resource registrar.
     *
     * @var \Illuminate\Routing\ResourceRegistrar
     */
    protected $registrar;

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
     * @var array
     */
    protected $options = [];

    /**
     * The resource's registration status.
     *
     * @var bool
     */
    protected $registered = false;

    /**
     * Create a new pending singleton resource registration instance.
     *
     * @param  \Illuminate\Routing\ResourceRegistrar  $registrar
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return void
     */
    public function __construct(ResourceRegistrar $registrar, $name, $controller, array $options)
    {
        $this->name = $name;
        $this->options = $options;
        $this->registrar = $registrar;
        $this->controller = $controller;
    }

    /**
     * Set the methods the controller should apply to.
     *
     * @param  array|string|mixed  $methods
     * @return \Illuminate\Routing\PendingSingletonResourceRegistration
     */
    public function only($methods)
    {
        return tap($this, fn () => $this->options['only'] = is_array($methods) ? $methods : func_get_args());
    }

    /**
     * Set the methods the controller should exclude.
     *
     * @param  array|string|mixed  $methods
     * @return \Illuminate\Routing\PendingSingletonResourceRegistration
     */
    public function except($methods)
    {
        return tap($this, fn () => $this->options['except'] = is_array($methods) ? $methods : func_get_args());
    }

    /**
     * Indicate that the resource should have creation and storage routes.
     *
     * @return $this
     */
    public function creatable()
    {
        return tap($this, fn () => $this->options['creatable'] = true);
    }

    /**
     * Indicate that the resource should have a deletion route.
     *
     * @return $this
     */
    public function destroyable()
    {
        return tap($this, fn () => $this->options['destroyable'] = true);
    }

    /**
     * Set the route names for controller actions.
     *
     * @param  array|string  $names
     * @return \Illuminate\Routing\PendingSingletonResourceRegistration
     */
    public function names($names)
    {
        return tap($this, fn () => $this->options['names'] = $names);
    }

    /**
     * Set the route name for a controller action.
     *
     * @param  string  $method
     * @param  string  $name
     * @return \Illuminate\Routing\PendingSingletonResourceRegistration
     */
    public function name($method, $name)
    {
        return tap($this, fn () => $this->options['names'][$method] = $name);
    }

    /**
     * Override the route parameter names.
     *
     * @param  array|string  $parameters
     * @return \Illuminate\Routing\PendingSingletonResourceRegistration
     */
    public function parameters($parameters)
    {
        return tap($this, fn () => $this->options['parameters'] = $parameters);
    }

    /**
     * Override a route parameter's name.
     *
     * @param  string  $previous
     * @param  string  $new
     * @return \Illuminate\Routing\PendingSingletonResourceRegistration
     */
    public function parameter($previous, $new)
    {
        return tap($this, fn () => $this->options['parameters'][$previous] = $new);
    }

    /**
     * Add middleware to the resource routes.
     *
     * @param  mixed  $middleware
     * @return \Illuminate\Routing\PendingSingletonResourceRegistration
     */
    public function middleware($middleware)
    {
        $middleware = Arr::wrap($middleware);

        foreach ($middleware as $key => $value) {
            $middleware[$key] = (string) $value;
        }

        $this->options['middleware'] = $middleware;

        return $this;
    }

    /**
     * Specify middleware that should be removed from the resource routes.
     *
     * @param  array|string  $middleware
     * @return $this|array
     */
    public function withoutMiddleware($middleware)
    {
        $this->options['excluded_middleware'] = array_merge(
            (array) ($this->options['excluded_middleware'] ?? []), Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Add "where" constraints to the resource routes.
     *
     * @param  mixed  $wheres
     * @return \Illuminate\Routing\PendingSingletonResourceRegistration
     */
    public function where($wheres)
    {
        return tap($this, fn () => $this->options['wheres'] = $wheres);
    }

    /**
     * Register the singleton resource route.
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    public function register()
    {
        $this->registered = true;

        return $this->registrar->singleton(
            $this->name, $this->controller, $this->options
        );
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        if (! $this->registered) {
            $this->register();
        }
    }
}
