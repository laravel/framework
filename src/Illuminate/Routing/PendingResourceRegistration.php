<?php

namespace Illuminate\Routing;

class PendingResourceRegistration
{
    /**
     * The resource registrar.
     *
     * @var \Illuminate\Routing\ResourceRegistrar
     */
    protected $registrar;

    /**
     * The resource information.
     *
     * @var array
     */
    protected $resource;

    /**
     * Create a new pending resource registration instance.
     *
     * @param  \Illuminate\Routing\ResourceRegistrar  $registrar
     * @param  array  $resource
     * @return void
     */
    public function __construct(ResourceRegistrar $registrar, array $resource)
    {
        $this->registrar = $registrar;
        $this->resource = $resource;
    }

    /**
     * Route a resource to a controller.
     *
     * @return void
     */
    public function register()
    {
        $this->registrar->register(
            $this->resource['name'],
            $this->resource['controller'],
            $this->resource['options']
        );
    }

    /**
     * Set the methods the controller should apply to.
     *
     * @param  array|string|dynamic  $methods
     */
    public function only($methods)
    {
        $this->resource['options']['only'] = is_array($methods) ? $methods : func_get_args();
    }

    /**
     * Set the methods the controller should exclude.
     *
     * @param  array|string|dynamic  $methods
     */
    public function except($methods)
    {
        $this->resource['options']['except'] = is_array($methods) ? $methods : func_get_args();
    }
}
