<?php

namespace Illuminate\Routing;

class PendingResourceRegistration
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
     * The resource registrar.
     *
     * @var \Illuminate\Routing\ResourceRegistrar
     */
    protected $registrar;

    /**
     * Create a new pending resource registration instance.
     *
     * @param  \Illuminate\Routing\ResourceRegistrar  $registrar
     * @return void
     */
    public function __construct(ResourceRegistrar $registrar)
    {
        $this->registrar = $registrar;
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->registrar->register($this->name, $this->controller, $this->options);
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
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function only($methods)
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }

    /**
     * Set the methods the controller should exclude.
     *
     * @param  array|string|dynamic  $methods
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function except($methods)
    {
        $this->options['except'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }

    /**
     * Set the route names for controller actions.
     *
     * @param  array  $names
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function names(array $names)
    {
        $this->options['names'] = $names;

        return $this;
    }

    /**
     * Set the methods the controller should exclude.
     *
     * @param  string  $method
     * @param  string  $name
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function name($method, $name)
    {
        if (! isset($this->options['names'])) {
            $this->options['names'] = [];
        }

        $this->options['names'][$method] = $name;

        return $this;
    }

    /**
     * Override the route parameter names.
     *
     * @param  array  $parameters
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function parameters(array $parameters)
    {
        $this->options['parameters'] = $parameters;

        return $this;
    }

    /**
     * Override the route parameter name.
     *
     * @param  string  $previous
     * @param  string  $new
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function parameter($previous, $new)
    {
        if (! isset($this->options['parameters'])) {
            $this->options['parameters'] = [];
        }

        $this->options['parameters'][$previous] = $new;

        return $this;
    }
}
