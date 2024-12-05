<?php

namespace Illuminate\Container\Concerns;

use Illuminate\Container\Container;

trait HasContainer
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Set the container instance used by the gate.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
