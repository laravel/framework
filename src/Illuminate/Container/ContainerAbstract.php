<?php

namespace Illuminate\Container;

use ArrayAccess;
use Illuminate\Container\Traits\ArrayAccessTrait;

class ContainerAbstract extends ContainerResolver implements ArrayAccess
{
    use ArrayAccessTrait;

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

    /**
     * Resolve something (binded or not)
     *
     * @param  mixed $subject
     * @param  array  $parameters
     * @return mixed
     */
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

    /**
     * Bind a plain value
     * @param  string $abstract
     * @param  mixed $concrete
     * @return void
     */
    public function bindPlain($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::IS_RESOLVED => false,
            self::BINDING_TYPE => self::TYPE_PLAIN
        ];
    }

    /**
     * Bind a value which need to be resolved each time
     * @param  string $abstract
     * @param  mixed $concrete
     * @return void
     */
    public function bindService($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::IS_RESOLVED => false,
            self::BINDING_TYPE => self::TYPE_SERVICE
        ];
    }

    /**
     * Bind a value which need to be resolved one time
     * @param  string $abstract
     * @param  mixed $concrete
     * @return void
     */
    public function bindSingleton($abstract, $concrete)
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::IS_RESOLVED => false,
            self::BINDING_TYPE => self::TYPE_SINGLETON
        ];
    }

    /**
     * Resolve a plain value from the container
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolvePlain($abstract, array $parameters = [])
    {
        return $this->bindings[$abstract][self::VALUE];
    }

    /**
     * Resolve a service from the container
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveService($abstract, array $parameters = [])
    {
        $binding = $this->bindings[$abstract];

        return parent::resolve($binding[self::VALUE], $parameters);
    }

    /**
     * Resolve a singleton from the container
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveSingleton($abstract, array $parameters = [])
    {
        $binding = $this->bindings[$abstract];
        $resolved = parent::resolve($binding[self::VALUE], $parameters);

        $this->bindPlain($abstract, $resolved);

        return $resolved;
    }
}
