<?php

namespace Illuminate\Container;

use Closure;
use Resolver;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Container extends AbstractContainer implements ContainerContract
{
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
        $concrete = $this->normalize($concrete);

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
     * Wrap a Closure such that it is shared.
     *
     * @param  \Closure  $closure
     * @return \Closure
     */
    public function share(Closure $closure)
    {
        return function ($container) use ($closure) {
            static $object;

            if (is_null($object)) {
                $object = $closure($container);
            }

            return $object;
        };
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

        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract][AbstractContainer::VALUE] instanceof Closure) {
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
            $segments = explode('@', $callback, 2);
            $resolved = $this->resolve($segments[0], [], Resolver::TYPE_CLASS);

            return $this->resolve([$resolved, $segments[1]], $parameters, Resolver::TYPE_METHOD);
        } else if (is_string($callback) && $defaultMethod) {
            $resolved = $this->resolve($callback, [], Resolver::TYPE_CLASS);

            return $this->resolve([$resolved, $defaultMethod], $parameters, Resolver::TYPE_METHOD);
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
		$abstract = $this->normalize($abstract);

    	return isset($this->bindings[$abstract]);
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

        if (isset($this->bindings[$abstract]) && is_object($this->bindings[$abstract])) {
            return true;
        }

        return false;
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
     * Assign a set of tags to a given binding.
     *
     * @param  array|string  $abstracts
     * @param  array|mixed   ...$tags
     * @return void
     */
    public function tag($abstracts, $tags)
    {
    }

    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param  array  $tag
     * @return array
     */
    public function tagged($tag)
    {
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
    }

    /**
     * "Extend" an abstract type in the container.
     *
     * @param  string    $abstract
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure)
    {
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

    /**
     * Register a new resolving callback.
     *
     * @param  string    $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function resolving($abstract, Closure $callback = null)
    {
    }

   /**
     * Register a new after resolving callback.
     *
     * @param  string    $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null)
    {
    }

}
