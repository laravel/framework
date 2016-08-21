<?php

namespace Illuminate\Container;

use Closure;

class Container extends AbstractContainer
{
    private function normalize($service)
    {
        return is_string($service) ? ltrim($service, '\\') : $service;
    }

	public function bind($abstract, $concrete = null, $shared = false)
	{
		return $this->bindPlain($abstract, $concrete);
	}

    public function singleton($abstract, $concrete = null)
    {
        return $this->bindSingleton($abstract, $concrete);
    }

    public function instance($abstract, $instance)
    {
        return $this->bindInstance($abstract, $instance);
    }

    public function bound($abstract)
    {
		$abstract = $this->normalize($abstract);

    	return isset($this->bindings[$abstract]);
    }

    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        //Call class@method, callable or class + method (not from container)
        return $this->resolve($abstract, $parameters);
    }

    public function resolved($abstract)
    {
        $abstract = $this->normalize($abstract);

        if (isset($this->bindings[$abstract]) && is_object($this->bindings[$abstract])) {
            return true;
        }

        return false;
    }

    public function alias($abstract, $alias)
    {
    }
    public function tag($abstracts, $tags)
    {
    }
    public function tagged($tag)
    {
    }
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
    }
    public function extend($abstract, Closure $closure)
    {
    }
    public function when($concrete)
    {
    }
    public function resolving($abstract, Closure $callback = null)
    {
    }
    public function afterResolving($abstract, Closure $callback = null)
    {
    }

}
