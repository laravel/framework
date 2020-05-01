<?php

namespace Illuminate\Support\Traits;

use Closure;
use ReflectionFunction;
use ReflectionParameter;
use RuntimeException;

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
    protected function parameterTypes(Closure $closure)
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
     * Get the class of the first parameter of the given closure.
     *
     * @param  Closure  $closure
     * @return string
     *
     * @throws \ReflectionException|\RunTimeException
     */
    protected function firstParameterType(Closure $closure)
    {
        $types = $this->parameterTypes($closure);

        if (! $types) {
            throw new RunTimeException('The given closure has no parameters');
        }

        if ($types[0] === null) {
            throw new RunTimeException('The first parameter of the given closure has no class type hint');
        }

        return $types[0];
    }
}
