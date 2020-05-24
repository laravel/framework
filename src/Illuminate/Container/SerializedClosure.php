<?php

namespace Illuminate\Container;

use Closure;
use InvalidArgumentException;
use Opis\Closure\SerializableClosure;

class SerializedClosure
{
    /**
     * Parse serialized string into callable Closure.
     *
     * @param string $serializedClosure
     * @return Closure
     */
    public static function fromString($serializedClosure)
    {
        $serializableClosure = unserialize($serializedClosure);

        if ($serializableClosure instanceof SerializableClosure) {
            return $serializableClosure->getClosure();
        }

        throw new InvalidArgumentException('Provided value is not a valid SerializableClosure');
    }

    /**
     * Serialize callable Closure into string.
     *
     * @param Closure $closure
     * @return string
     */
    public static function toString($closure)
    {
        return self::class . '@' . serialize(new SerializableClosure($closure));
    }
}
