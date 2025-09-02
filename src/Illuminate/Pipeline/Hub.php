<?php

namespace Illuminate\Pipeline;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pipeline\Hub as HubContract;

class Hub implements HubContract
{
    /**
     * The container implementation.
     *
     * @var \Illuminate\Contracts\Container\Container|null
     */
    protected $container;

    /**
     * All of the available pipelines.
     *
     * @var array
     */
    protected $pipelines = [];

    /**
     * Create a new Hub instance.
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Define the default named pipeline.
     *
     * @return void
     */
    public function defaults(Closure $callback)
    {
        $this->pipeline('default', $callback);
    }

    /**
     * Define a new named pipeline.
     *
     * @param  string  $name
     * @return void
     */
    public function pipeline($name, Closure $callback)
    {
        $this->pipelines[$name] = $callback;
    }

    /**
     * Send an object through one of the available pipelines.
     *
     * @param  string|null  $pipeline
     */
    public function pipe($object, $pipeline = null)
    {
        $pipeline = $pipeline ?: 'default';

        return call_user_func(
            $this->pipelines[$pipeline], new Pipeline($this->container), $object
        );
    }

    /**
     * Get the container instance used by the hub.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the container instance used by the hub.
     *
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
