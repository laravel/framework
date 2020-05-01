<?php

namespace Illuminate\Support\Traits;

use Closure;
use ReflectionFunction;
use ReflectionParameter;
use RuntimeException;

trait ReflectsClosures
{
    /**
     * Get the class names / types of the parameters of the given Closure.
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
                return;
            }

            return $parameter->getClass()->name ?? null;
        }, $reflection->getParameters());
    }

    /**
     * Get the class name of the first parameter of the given Closure.
     *
     * @param  Closure  $closure
     * @return string
     *
     * @throws \ReflectionException|\RunTimeException
     */
    protected function firstClosureParameterType(Closure $closure)
    {
        $types = $this->closureParameterTypes($closure);

        if (! $types) {
            throw new RunTimeException('The given Closure has no parameters.');
        }

        if ($types[0] === null) {
            throw new RunTimeException('The first parameter of the given Closure is missing a type hint.');
        }

        return $types[0];
    }
}
