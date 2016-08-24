<?php

namespace Illuminate\Container;

use Closure;
use ReflectionException;
use Illuminate\Contracts\Container\BindingResolutionException;
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
     * @param  string|array  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
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
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null)
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
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
     * @param  string|array  $abstract
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
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = self::normalize($abstract);

        if ($abstract instanceof Closure) {
            return $this->resolve($abstract, [$this, $parameters]);
        }
        if ($this->bound($abstract) && $this->bindings[$abstract][ContainerAbstract::VALUE] instanceof Closure) {
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

    /**
     * Intercept the resolve call to add some features
     *
     * @param  mixed $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolve($abstract, array $parameters = [])
    {
        $resolved = parent::resolve($abstract, $parameters);
        $resolved = $this->extendResolved($abstract, $resolved);

        $this->fireAfterResolving($abstract, $resolved);

        return $resolved;
    }

    /**
     * Intercept the resolveParameter call to deal with contextual binding
     *
     * @param  \ReflectionParameter $parameter
     * @param  array                $parameters
     * @return mixed
     */
    protected function resolveParameter(\ReflectionParameter $parameter, array $parameters = [])
    {
        $contextualBinding = $this->resolveContextualBinding($parameter);

        if ($contextualBinding) {
            try {
                return $this->make($contextualBinding, $parameters);
            } catch (\Exception $e){
                return $contextualBinding;
            }
        }

        return parent::resolveParameter($parameter, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        $abstract = self::normalize($abstract);

    	return is_string($abstract) && isset($this->bindings[$abstract]);
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
