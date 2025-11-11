<?php

namespace Illuminate\Support\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;

trait InteractsWithContainer
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container|null
     */
    protected $container;

    /**
     * Get the container instance.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    protected function getContainer(): ContainerContract
    {
        return $this->container ?? Container::getInstance();
    }

    /**
     * Set the container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function setContainer(ContainerContract $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Resolve a service from the container.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $abstract
     * @param  array  $parameters
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     */
    protected function resolve($abstract, array $parameters = [])
    {
        return $this->getContainer()->make($abstract, $parameters);
    }

    /**
     * Determine if a service is bound in the container.
     *
     * @param  string  $abstract
     * @return bool
     */
    protected function bound(string $abstract): bool
    {
        return $this->getContainer()->bound($abstract);
    }

    /**
     * Register a binding with the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    protected function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->getContainer()->bind($abstract, $concrete, $shared);
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    protected function singleton(string $abstract, $concrete = null): void
    {
        $this->getContainer()->singleton($abstract, $concrete);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     * @return mixed
     */
    protected function instance(string $abstract, $instance)
    {
        return $this->getContainer()->instance($abstract, $instance);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array<string, mixed>  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function call($callback, array $parameters = [], $defaultMethod = null)
    {
        return $this->getContainer()->call($callback, $parameters, $defaultMethod);
    }

    /**
     * Determine if the container has a method binding for the given method.
     *
     * @param  string  $method
     * @return bool
     */
    protected function hasMethodBinding(string $method): bool
    {
        return $this->getContainer()->hasMethodBinding($method);
    }

    /**
     * Call a method binding for the given method.
     *
     * @param  string  $method
     * @param  mixed  $instance
     * @return mixed
     */
    protected function callMethodBinding(string $method, $instance)
    {
        return $this->getContainer()->callMethodBinding($method, $instance);
    }
}
