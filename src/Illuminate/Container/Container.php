<?php

namespace Illuminate\Container;

use Closure;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Container extends ContainerAbstract implements ContainerContract
{
    use Traits\TagsTrait;
    use Traits\AliasTrait;
    use Traits\EventsTrait;
    use Traits\ExtendersTrait;
    use Traits\ContextualBindingsTrait;

    /**
     * Register a binding with the container.
     *
     * @param  string|array          $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
	public function bind($abstract, $concrete = null)
	{
        $abstract = self::normalize($abstract);
        $concrete = ($concrete) ? self::normalize($concrete) : $abstract;

        if (is_array($abstract)) {
            $this->bindService(key($abstract), $concrete);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindService($abstract, $concrete);
        }
	}

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string                $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function bindIf($abstract, $concrete = null)
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete);
        }
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  mixed   $instance
     * @return void
     */
    public function instance($abstract, $instance)
    {
        $abstract = self::normalize($abstract);

        if (is_array($abstract)) {
            $this->bindPlain(key($abstract), $instance);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindPlain($abstract, $instance);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string|array          $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $abstract = self::normalize($abstract);
        $concrete = ($concrete) ? self::normalize($concrete) : $abstract;

        if (is_array($abstract)) {
            $this->bindSingleton(key($abstract), $concrete);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindSingleton($abstract, $concrete);
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  mixed  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = self::normalize($abstract);

        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve the given type from outside the container.
     *
     * @param  mixed  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function call($abstract, array $parameters = [])
    {
        $abstract = self::normalize($abstract);

        return (new ContainerAbstract())->resolveNonBound($abstract, $parameters);
    }

    /**
     * Intercept the resolve call to add some features
     *
     * @param  mixed  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolve($abstract, array $parameters = [])
    {
        if (is_string($abstract) && isset($this->contextualParameters[$abstract])) {
            $contextualParameters = $this->contextualParameters[$abstract];

            foreach ($contextualParameters as $key => $value) {
                if ($value instanceof Closure) {
                    $contextualParameters[$key] = $value($this);
                }
            }

            $parameters = array_replace($contextualParameters, $parameters);
        }

        if ($this->isBound($abstract)) {
            return $this->resolveBound($abstract, $parameters);
        } else {
            return $this->resolveNonBound($abstract, $parameters);
        }
    }

    /**
     * Resolve a bound type
     *
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveBound($abstract, array $parameters = [])
    {
        $binding = $this->bindings[$abstract];
        $concrete = $binding[ContainerAbstract::VALUE];

        if (ContainerAbstract::isComputed($binding)) {
            return $binding[ContainerAbstract::VALUE];
        }
        if ($concrete instanceof Closure) {
            array_unshift($parameters, $this);
        }

        $resolved = parent::resolveBound($abstract, $parameters);

        $this->extendResolved($abstract, $resolved);
        $this->afterResolvingCallback($concrete, $resolved, $abstract);

        return $resolved;
    }

    /**
     * Resolve a non bound type
     *
     * @param  mixed $concrete
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveNonBound($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            array_unshift($parameters, $this);
        }

        $resolved = parent::resolveNonBound($concrete, $parameters);

        $this->extendResolved($concrete, $resolved);
        $this->afterResolvingCallback($concrete, $resolved);

        return $resolved;
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return $this->isBound(self::normalize($abstract));
    }

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param  string $abstract
     * @return bool
     */
    public function resolved($abstract)
    {
        $abstract = self::normalize($abstract);

        return isset($this->bindings[$abstract]) && $this->bindings[$abstract][ContainerAbstract::IS_RESOLVED];
    }

    /**
     * Normalize the given class name by removing leading slashes.
     *
     * @param  mixed  $service
     * @return mixed
     */
    private static function normalize($service)
    {
        return is_string($service) ? ltrim($service, '\\') : $service;
    }
}
