<?php

namespace Illuminate\Container;

use ArrayAccess;
use Illuminate\Container\Traits\ArrayAccessTrait;

class ContainerAbstract extends ContainerResolver implements ArrayAccess
{
    use ArrayAccessTrait;

	const TYPE_PLAIN = 0;
    const TYPE_SERVICE = 1;
    const TYPE_SINGLETON = 2;

    const VALUE = 0;
    const IS_RESOLVED = 1;
    const BINDING_TYPE = 2;

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
            $bindingType = $this->bindings[$subject][self::BINDING_TYPE];

            if ($bindingType === self::TYPE_PLAIN) {
                return $this->resolvePlain($subject, $parameters);
            } else if ($bindingType === self::TYPE_SERVICE) {
                return $this->resolveService($subject, $parameters);
            }

            return $this->resolveSingleton($subject, $parameters);
        }

        return parent::resolve($subject, $parameters);
    }

    public function isBinded($abstract)
    {
        return is_string($abstract) && isset($this->bindings[$abstract]);
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
            self::IS_RESOLVED => true,
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
        $this->bindings[$abstract][self::IS_RESOLVED] = true;

        return parent::resolve($this->bindings[$abstract][self::VALUE], $parameters);
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

        if ($binding[self::IS_RESOLVED]) {
            return $binding[self::VALUE];
        }

        $this->bindings[$abstract][self::IS_RESOLVED] = true;
        $this->bindings[$abstract][self::VALUE] = parent::resolve($binding[self::VALUE], $parameters);

        return $this->bindings[$abstract][self::VALUE];
    }
}
