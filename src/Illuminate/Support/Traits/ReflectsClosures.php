<?php

namespace Illuminate\Support\Traits;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Reflector;
use ReflectionFunction;
use RuntimeException;

trait ReflectsClosures
{
    /**
     * Get the class name of the first parameter of the given Closure.
     *
     * @param  \Closure  $closure
     * @return string
     *
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    protected function firstClosureParameterType(Closure $closure)
    {
        $types = array_values($this->closureParameterTypes($closure));

        if (! $types) {
            throw new RuntimeException('The given Closure has no parameters.');
        }

        if ($types[0] === null) {
            throw new RuntimeException('The first parameter of the given Closure is missing a type hint.');
        }

        return $types[0];
    }

    /**
     * Get the class names of the first parameter of the given Closure, including union types.
     *
     * @param  \Closure  $closure
     * @return array
     *
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    protected function firstClosureParameterTypes(Closure $closure)
    {
        $reflection = new ReflectionFunction($closure);

        $types = (new Collection($reflection->getParameters()))->mapWithKeys(function ($parameter) {
            if ($parameter->isVariadic()) {
                return [$parameter->getName() => null];
            }

            return [$parameter->getName() => Reflector::getParameterClassNames($parameter)];
        })->filter()->values()->all();

        if (empty($types)) {
            throw new RuntimeException('The given Closure has no parameters.');
        }

        if (isset($types[0]) && empty($types[0])) {
            throw new RuntimeException('The first parameter of the given Closure is missing a type hint.');
        }

        return $types[0];
    }

    /**
     * Get the class names / types of the parameters of the given Closure.
     *
     * @param  \Closure  $closure
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function closureParameterTypes(Closure $closure)
    {
        $reflection = new ReflectionFunction($closure);

        return (new Collection($reflection->getParameters()))->mapWithKeys(function ($parameter) {
            if ($parameter->isVariadic()) {
                return [$parameter->getName() => null];
            }

            return [$parameter->getName() => Reflector::getParameterClassName($parameter)];
        })->all();
    }
}
