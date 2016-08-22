<?php

namespace Illuminate\Container;

use ArrayAccess;

class AbstractContainer extends Resolver implements ArrayAccess
{
    use ArrayContainerTrait;

	const TYPE_PLAIN = 1;
    const TYPE_SERVICE = 2;
    const TYPE_SINGLETON = 3;

    const VALUE = "value";
    const IS_RESOLVED = "is_resolved";
    const BINDING_TYPE = "binding_type";

    private $RESOLVERS_MAP = [
        self::TYPE_PLAIN => "resolvePlain",
        self::TYPE_SERVICE => "resolveService",
        self::TYPE_SINGLETON => "resolveSingleton"
    ];

	protected $bindings = [];

    public function resolve($subject, array $parameters = [])
    {
        if (is_string($subject) && isset($this->bindings[$subject])) {
            $binding = $this->bindings[$subject];
            $this->bindings[$subject][self::IS_RESOLVED] = true;

            $resolver = $this->RESOLVERS_MAP[$binding[self::BINDING_TYPE]];

            return call_user_func_array([$this, $resolver], [$subject, $parameters]);
        }

        return parent::resolve($subject, $parameters);
    }

    public function bindPlain($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::IS_RESOLVED => false,
            self::BINDING_TYPE => self::TYPE_PLAIN
        ];
    }

    public function bindService($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::IS_RESOLVED => false,
            self::BINDING_TYPE => self::TYPE_SERVICE
        ];
    }

    public function bindSingleton($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::IS_RESOLVED => false,
            self::BINDING_TYPE => self::TYPE_SINGLETON
        ];
    }

    public function resolvePlain($abstract, array $parameters = [])
    {
        return $this->bindings[$abstract][self::VALUE];
    }

    public function resolveService($abstract, array $parameters = [])
    {
        $binding = $this->bindings[$abstract];
        $resolved = parent::resolve($binding[self::VALUE], $parameters);

        return $resolved;
    }

    public function resolveSingleton($abstract, array $parameters = [])
    {
        $binding = $this->bindings[$abstract];
        $resolved = parent::resolve($binding[self::VALUE], $parameters);

        $this->bindPlain($abstract, $resolved);

        return $resolved;
    }
}
