<?php

namespace Illuminate\Container\Traits;

use Closure;
use Illuminate\Container\ContainerAbstract;

trait ExtendersTrait
{
    private $extenders = [];

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
        $abstract = self::normalize($abstract);

        $this->extenders[$abstract][] = $closure;
    }

    /**
     * "Extend" a resolved subject
     * @param  mixed $abstract
     * @param  mixed $resolved
     * @return mixed
     */
    private function extendResolved($abstract, $resolved)
    {
        if (!is_string($abstract) || !isset($this->extenders[$abstract])) {
            return $resolved;
        }
        foreach ($this->extenders[$abstract] as $extender) {
            $resolved = $extender($resolved, $this);
        }
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract][self::BINDING_TYPE] !== self::TYPE_SERVICE) {
            unset($this->extenders[$abstract]);
            $this->bindPlain($abstract, $resolved);
        }

        return $resolved;
    }

}
