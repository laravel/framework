<?php

namespace Illuminate\Support;

use Illuminate\Support\Exceptions\UntraceableOnceException;

class Onceable
{
    /**
     * Create a new onceable instance.
     *
     * @param  string  $hash
     * @param  object|null  $object
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
     * Tries to create a new onceable instance from the given trace.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @return static|null
     */
    public static function tryFromTrace(array $trace, callable $callable)
    {
        if (! is_null($hash = static::hashFromTrace($trace))) {
            $object = static::objectFromTrace($trace);

            return new static($hash, $object, $callable);
        }
    }

    /**
     * Computes the object of the onceable from the given trace, if any.
     *
     * @param  array<int, array<string, mixed>>  $trace
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
     * @return string|null
     */
    protected static function hashFromTrace(array $trace)
    {
        if (str_contains($trace[0]['file'] ?? '', 'eval()\'d code')) {
            return null;
        }

        $arguments = array_map(
            fn (mixed $argument) => is_object($argument) ? spl_object_hash($argument) : $argument,
            $trace[1]['args'] ?? [],
        );

        $prefix = ($trace[1]['class'] ?? '').$trace[1]['function'];

        if (str_contains($prefix, '{closure}')) {
            $prefix = $trace[0]['line'];
        }

        return md5($prefix.serialize($arguments));
    }
}
