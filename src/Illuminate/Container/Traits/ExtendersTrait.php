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
        $this->extendAbstract(self::normalize($abstract), $closure);
    }

    private function extendAbstract($abstract, Closure $closure)
    {
        $this->extenders[$abstract][] = $closure;
    }

    private function extendConcrete($concrete, Closure $closure)
    {
        return $closure($concrete, $this);
    }

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
