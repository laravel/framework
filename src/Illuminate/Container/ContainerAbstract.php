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
    const PARAMETERS = 1;
    const IS_RESOLVED = 2;
    const BINDING_TYPE = 3;

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
        if ($this->isBinded($subject)) {
            return $this->resolveBinded($subject, $parameters);
        } else {
            return $this->resolveNonBinded($subject, $parameters);
        }
    }

    /**
     * Resolve a binded type
     *
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveBinded($abstract, array $parameters = [])
    {
        $bindingType = $this->bindings[$abstract][self::BINDING_TYPE];

        if ($bindingType === self::TYPE_PLAIN) {
            return $this->resolvePlain($abstract);
        } else if ($bindingType === self::TYPE_SERVICE) {
            return $this->resolveService($abstract, $parameters);
        } else {
            return $this->resolveSingleton($abstract, $parameters);
        }
    }

    /**
     * Resolve a non binded type
     *
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveNonBinded($concrete, array $parameters = [])
    {
        if (is_string($concrete) && strpos($concrete, '@')) {
            $parts = explode('@', $concrete, 2);
            $concrete = [$this->resolve($parts[0]), $parts[1]];
        }

        return parent::resolve($concrete, $parameters);
    }

    /**
     * Check if an abstract is binded to the container
     *
     * @param  string  $abstract
     * @return boolean
     */
    public function isBinded($abstract)
    {
        return is_string($abstract) && isset($this->bindings[$abstract]);
    }

    /**
     * Check if a binding is computed
     *
     * @param  array  $binding
     * @return boolean
     */
    public static function isComputed($binding)
    {
        return $binding[self::IS_RESOLVED] && $binding[self::BINDING_TYPE] !== self::TYPE_SERVICE;
    }

    /**
     * Bind a plain value
     *
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
     *
     * @param  string $abstract
     * @param  mixed  $concrete
     * @param  array  $parameters
     * @return void
     */
    public function bindService($abstract, $concrete, $parameters = [])
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::PARAMETERS => $parameters,
            self::IS_RESOLVED => false,
            self::BINDING_TYPE => self::TYPE_SERVICE
        ];
    }

    /**
     * Bind a value which need to be resolved one time
     *
     * @param  string $abstract
     * @param  mixed  $concrete
     * @param  array  $parameters
     * @return void
     */
    public function bindSingleton($abstract, $concrete, $parameters = [])
    {
        $this->bindings[$abstract] = [
            self::VALUE => $concrete,
            self::PARAMETERS => $parameters,
            self::IS_RESOLVED => false,
            self::BINDING_TYPE => self::TYPE_SINGLETON
        ];
    }

    /**
     * Resolve a plain value from the container
     *
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolvePlain($abstract)
    {
        $binding = &$this->bindings[$abstract];
        $binding[self::IS_RESOLVED] = true;

        return $binding[self::VALUE];
    }

    /**
     * Resolve a service from the container
     *
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveService($abstract, array $parameters = [])
    {
        $binding = &$this->bindings[$abstract];

        $binding[self::IS_RESOLVED] = true;
        $parameters = array_merge($parameters, $binding[self::PARAMETERS]);

        return parent::resolve($binding[self::VALUE], $parameters);
    }

    /**
     * Resolve a singleton from the container
     *
     * @param  string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveSingleton($abstract, array $parameters = [])
    {
        $binding = &$this->bindings[$abstract];

        if ($binding[self::IS_RESOLVED]) {
            return $binding[self::VALUE];
        }

        $binding[self::IS_RESOLVED] = true;
        $parameters = array_merge($parameters, $binding[self::PARAMETERS]);

        $binding[self::VALUE] = parent::resolve($binding[self::VALUE], $parameters);

        return $binding[self::VALUE];
    }
}
