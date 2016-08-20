<?php

namespace Illuminate\Container;

use Closure;
use Illuminate\Container\Resolver;

class Container extends Resolver
{
	const TYPE_PLAIN = 1;
	const TYPE_INSTANCE = 2;
	const TYPE_SINGLETON = 3;

	private $bindings = [];
	private $bindingsTypes = [];

	private function normalize($service)
	{
        return is_string($service) ? ltrim($service, '\\') : $service;
	}

    private function bindPlain($abstract, $concrete = null)
    {
        $abstract = $this->normalize($abstract);
        $concrete = $this->normalize($concrete);

        $this->bindings[$abstract] = $concrete;
        $this->bindingsTypes[$abstract] = self::TYPE_PLAIN;
    }

    private function bindInstance($abstract, $concrete = null)
    {
        $abstract = $this->normalize($abstract);
        $concrete = $this->normalize($concrete);

        $this->bindings[$abstract] = $concrete;
        $this->bindingsTypes[$abstract] = self::TYPE_INSTANCE;
    }

    private function bindSingleton($abstract, $concrete = null)
    {
        $abstract = $this->normalize($abstract);
        $concrete = $this->normalize($concrete);

        $this->bindings[$abstract] = $concrete;
        $this->bindingsTypes[$abstract] = self::TYPE_SINGLETON;
    }

    private function resolveFromContainer($abstract, array $parameters = [])
    {
        $bind = $this->bindings[$abstract];
        $bindType = $this->bindingsTypes[$abstract];

        if (!is_object($bind)) {
            $bind = parent::resolve($abstract, $parameters);
        }
        if ($bindType === self::TYPE_SINGLETON) {
            $this->bindings[$abstract] = $bind;
        }

        return $bind;
    }

    public function resolve($abstract, array $parameters = [])
    {
        $abstract = $this->normalize($abstract);

        if (is_string($abstract) && isset($this->bindings[$abstract])) {
            $bind = $this->resolveFromContainer($abstract, $parameters);
        } else {
            $bind = parent::resolve($abstract, $parameters);
        }

        return $bind;
    }

	////////////////////////////

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
    public function make($abstract, array $parameters = [])
    {
    }
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
    }
    public function resolved($abstract)
    {
    }
    public function resolving($abstract, Closure $callback = null)
    {
    }
    public function afterResolving($abstract, Closure $callback = null)
    {
    }

}
