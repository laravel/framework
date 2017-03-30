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
     */
    public function only($methods)
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();
    }

    /**
     * Set the methods the controller should exclude.
     *
     * @param  array|string|dynamic  $methods
     */
    public function except($methods)
    {
        $this->options['except'] = is_array($methods) ? $methods : func_get_args();
    }
}
