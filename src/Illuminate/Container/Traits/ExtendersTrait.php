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
     */
    public function extend($abstract, Closure $closure)
    {
        $this->extendAbstract(self::normalize($abstract), $closure);
    }

    /**
     * "Extend" an abstract type in the container.
     *
     * @param  string    $abstract
     * @param  \Closure  $closure
     * @return void
     */
    private function extendAbstract($abstract, Closure $closure)
    {
        $this->extenders[$abstract][] = $closure;
    }

    /**
     * Call the given closure
     *
     * @param  mixed   $concrete
     * @param  Closure $closure
     * @return mixed
     */
    private function extendConcrete($concrete, Closure $closure)
    {
        return $closure($concrete, $this);
    }

    /**
     * Extend a resolved subject
     *
     * @param  string $abstract
     * @param  mixed &$resolved
     * @return void
     */
    private function extendResolved($abstract, &$resolved)
    {
        if (!is_string($abstract) || !isset($this->extenders[$abstract])) {
            return ;
        }

        $binding = $this->bindings[$abstract];

        foreach ($this->extenders[$abstract] as $extender) {
            $resolved = $this->extendConcrete($resolved, $extender);
        }

        if ($binding[ContainerAbstract::BINDING_TYPE] !== ContainerAbstract::TYPE_SERVICE) {
            unset($this->extenders[$abstract]);

            $this->bindings[$abstract][ContainerAbstract::VALUE] = $resolved;
        }
    }
}
