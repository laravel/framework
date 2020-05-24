<?php

namespace Illuminate\Container;

use Closure;
use InvalidArgumentException;
use Opis\Closure\SerializableClosure;

class SerializedClosure
{
    /**
     * @param string $serializedClosure
     * @return Closure
     */
    public static function toClosure($serializedClosure)
    {
        $serializableClosure = unserialize($serializedClosure);

        if ($serializableClosure instanceof SerializableClosure) {
            return $serializableClosure->getClosure();
        }

        throw new InvalidArgumentException('Provided value is not a valid SerializableClosure');
    }
}
