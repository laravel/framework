<?php

namespace Illuminate\Container;

use Closure;
use ReflectionException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Container extends ContainerAbstract implements ContainerContract
{
    use Traits\TagsTrait;
    use Traits\EventsTrait;
    use Traits\ExtendersTrait;

    public function resolve($abstract, array $parameters = [])
    {
        $resolved = parent::resolve($abstract, $parameters);
        $resolved = $this->extendResolved($abstract, $resolved);

        $this->fireAfterResolving($abstract, $resolved);

        return $resolved;
    }

    /**
     * Register a binding with the container.
     *
     * @param  string|array  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
	public function bind($abstract, $concrete = null, $shared = false)
	{
        $abstract = $this->normalize($abstract);
        $concrete = ($concrete) ? $this->normalize($concrete) : $abstract;

        if (is_array($abstract)) {
            $this->bindService(key($abstract), $concrete);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindService($abstract, $concrete);
        }
	}

    /**
     * Register a shared binding in the container.
     *
     * @param  string|array  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $abstract = $this->normalize($abstract);
        $concrete = ($concrete) ? $this->normalize($concrete) : $abstract;

        if (is_array($abstract)) {
            $this->bindSingleton(key($abstract), $concrete);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindSingleton($abstract, $concrete);
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
        $abstract = $this->normalize($abstract);

        if (is_array($abstract)) {
            $this->bindPlain(key($abstract), $instance);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindPlain($abstract, $instance);
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->normalize($abstract);

        if (is_string($abstract) && isset($this->bindings[$abstract]) && $this->bindings[$abstract][ContainerAbstract::VALUE] instanceof Closure) {
            return $this->resolve($abstract, [$this, $parameters]);
        }

        return $this->resolve($abstract, $parameters);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        if (is_string($callback) && strpos($callback, '@')) {
            list($callback, $defaultMethod) = explode('@', $callback, 2);
        }
        if (is_string($callback) && $defaultMethod) {
            return $this->resolve([$this->resolve($callback), $defaultMethod], $parameters);
        }

        return $this->resolve($callback, $parameters);
    }

    private function normalize($service)
    {
        return is_string($service) ? ltrim($service, '\\') : $service;
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
    	return isset($this->bindings[$this->normalize($abstract)]);
    }

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param  string $abstract
     * @return bool
     */
    public function resolved($abstract)
    {
        $abstract = $this->normalize($abstract);

        return isset($this->bindings[$abstract]) && $this->bindings[$abstract][ContainerAbstract::IS_RESOLVED];
    }

    /**
     * Alias a type to a different name.
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     */
    public function alias($abstract, $alias)
    {
        $alias = $this->normalize($alias);
        $abstract = $this->normalize($abstract);

        $this->bindings[$alias] = &$this->bindings[$abstract];
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Define a contextual binding.
     *
     * @param  string  $concrete
     * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
     */
    public function when($concrete)
    {
    }

}
