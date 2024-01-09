<?php

namespace Illuminate\Contracts\Container;

interface DependencyResolver
{
    /**
     * Resolve the dependency from the container.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     */
    public function resolve(Container $container): mixed;
}
