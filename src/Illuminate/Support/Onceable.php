<?php

namespace Illuminate\Support;

class Onceable
{
    /**
     * Create a new onceable instance.
     *
     * @param  string  $hash
     * @param  mixed  $object
     * @param  callable  $callable
     * @return void
     */
    public function __construct(
        public string $hash,
        public object|null $object,
        public $callable
    ) {
        //
    }

    /**
     * Creates a new onceable instance from the given trace.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @return static
     */
    public static function fromTrace(array $trace, callable $callable)
    {
        $hash = static::hashFromTrace($trace);
        $object = static::objectFromTrace($trace);

        return new static($hash, $object, $callable);
    }

    /**
     * Computes the object of the onceable from the given trace, if any.
     *
     * @param  array<int, array<string, mixed>> $trace
     * @return object|null
     */
    protected static function objectFromTrace(array $trace)
    {
        return $trace[1]['object'] ?? null;
    }

    /**
     * Computes the hash of the onceable from the given trace.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @return string
     */
    public static function hashFromTrace(array $trace)
    {
        $arguments = array_map(
            fn (mixed $argument) => is_object($argument) ? spl_object_hash($argument) : $argument,
            $trace[1]['args']
        );

        $prefix = ($trace[1]['class'] ?? '') . $trace[1]['function'];

        if (str_contains($prefix, '{closure}')) {
            $prefix = $trace[0]['line'];
        }

        return md5($prefix.serialize($arguments));
    }
}
