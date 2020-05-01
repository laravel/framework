<?php

namespace Illuminate\Support\Traits;

use Closure;
use ReflectionFunction;
use ReflectionParameter;

trait ReflectsClosures
{
    /**
     * Get the class types of the parameters of the given closure.
     *
     * @param  Closure  $closure
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function closureParameterTypes(Closure $closure)
    {
        $reflection = new ReflectionFunction($closure);

        return array_map(function (ReflectionParameter $parameter) {
            if ($parameter->isVariadic()) {
                return null;
            }

            return $parameter->getClass()->name ?? null;
        }, $reflection->getParameters());
    }
}
