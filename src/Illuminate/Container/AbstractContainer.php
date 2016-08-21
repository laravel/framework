<?php

namespace Illuminate\Container;

use ArrayAccess;

class AbstractContainer extends Resolver implements ArrayAccess
{
	const TYPE_PLAIN = 1;
    const TYPE_SERVICE = 2;
    const TYPE_SINGLETON = 3;

    const VALUE = "value";
    const BINDING_TYPE = "binding_type";
    const CONCRETE_TYPE = "concrete_type";

    private $RESOLVERS_MAP = [
        self::TYPE_PLAIN => "resolvePlain",
        self::TYPE_SERVICE => "resolveService",
        self::TYPE_SINGLETON => "resolveSingleton"
    ];

	protected $bindings = [];

    public function resolve($abstract, array $parameters = [], $type = null)
    {
        if (is_string($abstract) && isset($this->bindings[$abstract])) {
            $bindingType = $this->bindings[$abstract][self::BINDING_TYPE];
            $resolver = $this->RESOLVERS_MAP[$bindingType];

            $value = call_user_func_array([$this, $resolver], [$abstract, $parameters]);
        } else {
            $value = parent::resolve($abstract, $parameters, $type);
        }

        return $value;
    }

    public function bindPlain($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::BINDING_TYPE => self::TYPE_PLAIN
        ];
    }

    public function bindService($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::BINDING_TYPE => self::TYPE_SERVICE,
            self::CONCRETE_TYPE => parent::getType($concrete)
        ];
    }

    public function bindSingleton($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::BINDING_TYPE => self::TYPE_SINGLETON,
            self::CONCRETE_TYPE => parent::getType($concrete)
        ];
    }

    private function resolvePlain($abstract, array $parameters = [])
    {
        return $this->bindings[$abstract][self::VALUE];
    }

    private function resolveService($abstract, array $parameters = [])
    {
        $binding = $this->bindings[$abstract];
        $resolved = parent::resolve($binding[self::VALUE], $parameters, $binding[self::CONCRETE_TYPE]);

        return $resolved;
    }

    private function resolveSingleton($abstract, array $parameters = [])
    {
        $binding = $this->bindings[$abstract];
        $resolved = parent::resolve($binding[self::VALUE], $parameters, $binding[self::CONCRETE_TYPE]);

        $this->bindPlain($abstract, $resolved);

        return $resolved;
    }

    public function offsetGet($offset)
    {
        return $this->resolve($offset);
    }

    public function offsetSet($offset, $value)
    {
        if (is_string($offset)) {
            $this->bindPlain($offset, $value);
        } else {
            $this->bindService($offset, $value);
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->bindings[$offset]);
    }

    public function offsetExists($offset)
    {
        return isset($this->bindings[$offset]);
    }
}
