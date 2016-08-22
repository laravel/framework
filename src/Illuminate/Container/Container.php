<?php

namespace Illuminate\Container;

use Closure;
use Resolver;
use stdClass;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Container extends AbstractContainer implements ContainerContract
{
    private $tags = [];
    private $extenders = [];
    private $eventsAfterResolving;

    public function __construct()
    {
        $this->eventsAfterResolving = new Events();
    }

    public function resolve($abstract, array $parameters = [])
    {
        $concrete = parent::resolve($abstract, $parameters);

        if (is_string($abstract) && isset($this->extenders[$abstract])) {
            $extenders = $this->extenders[$abstract];

            unset($this->extenders[$abstract]);

            foreach ($extenders as $extender) {
                $concrete = $extender($concrete, $this);
            }

            $this->bindPlain($abstract, $concrete);
        }

        $this->fireAfterResolving($abstract, $concrete, [$concrete, $this]);

        return $concrete;
    }

    private function fireExtenders()
    {
    }

    private function fireAfterResolving($abstract, $concrete, $parameters = [])
    {
        $this->eventsAfterResolving->fireGlobal($parameters);
        $this->eventsAfterResolving->fire($abstract, $parameters);
        $this->eventsAfterResolving->fire($concrete, $parameters);
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

        return isset($this->bindings[$abstract]) && $this->bindings[$abstract][AbstractContainer::IS_RESOLVED];
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
        $tags = (is_array($tags)) ? $tags : array_slice(func_get_args(), 1);
        $abstracts = (is_array($abstracts)) ? $abstracts : [$abstracts];

        foreach ($abstracts as $key => $abstract) {
            $abstracts[$key] = $this->normalize($abstract);
        }
        foreach ($tags as $tagName) {
            if (isset($this->tags[$tagName])) {
                $this->tags[$tagName] = array_merge($this->tags[$tagName], $abstracts);
            } else {
                $this->tags[$tagName] = $abstracts;
            }
        }
    }

    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param  string  $tag
     * @return array
     */
    public function tagged($tag)
    {
        $results = [];

        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $abstract) {
                $results[] = $this->make($abstract);
            }
        }

        return $results;
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
        $abstract = $this->normalize($abstract);
        $this->extenders[$abstract][] = $closure;
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
        return $this->afterResolving($abstract, $callback);
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
        if (is_string($abstract)) {
            $abstract = $this->normalize($abstract);
        }

        return $this->eventsAfterResolving->listen($abstract, $callback);
    }

}
