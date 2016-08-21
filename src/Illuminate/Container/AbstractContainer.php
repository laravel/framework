<?php

namespace Illuminate\Container;

class AbstractContainer extends Resolver
{
	const TYPE_PLAIN = 1;
    const TYPE_SERVICE = 2;
    const TYPE_SINGLETON = 3;

    const VALUE = "value";
    const BINDING_TYPE = "binding_type";
    const CONCRETE_TYPE = "concrete_type";

    private $BINDINGS_RESOLVERS_MAP = [
        self::TYPE_PLAIN => "resolvePlain",
        self::TYPE_SERVICE => "resolveService",
        self::TYPE_SINGLETON => "resolveSingleton"
    ];

	protected $bindings = [];

    public function resolve($abstract, array $parameters = [])
    {
        if (is_string($abstract) && isset($this->bindings[$abstract])) {
            $bindingType = $this->bindings[$abstract][self::BINDING_TYPE];
            $resolver = $this->BINDINGS_RESOLVERS_MAP[$bindingType];

            $value = call_user_func_array([$this, $resolver], [$abstract, $parameters]);
        } else {
            $value = parent::resolve($abstract, $parameters);
        }

        return $value;
    }

    public function bindPlain($abstract, $concrete)
    {
        return $this->bind($abstract, $concrete, self::TYPE_PLAIN);
    }

    public function bindService($abstract, $concrete)
    {
        $concreteType = parent::getType($concrete);

        return $this->bind($abstract, $concrete, self::TYPE_SERVICE, $concreteType);
    }

    public function bindSingleton($abstract, $concrete)
    {
        $concreteType = parent::getType($concrete);

        return $this->bind($abstract, $concrete, self::TYPE_SINGLETON, $concreteType);
    }

    private function bind($abstract, $concrete, $bindingType, $concreteType = null)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::BINDING_TYPE => $bindingType,
            self::CONCRETE_TYPE => $concreteType
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
        $resolved = parent::resolve($binding[self::VALUE], $parameters);

        $this->bindPlain($abstract, $resolved);

        return $resolved;
    }
}
