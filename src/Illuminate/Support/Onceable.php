<?php

namespace Illuminate\Support;

use Closure;
use Laravel\SerializableClosure\Support\ReflectionClosure;

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
        if (! is_null($hash = static::hashFromTrace($trace, $callable))) {
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
    protected static function hashFromTrace(array $trace, callable $callable)
    {
        if (str_contains($trace[0]['file'] ?? '', 'eval()\'d code')) {
            return null;
        }

        $uses = array_map(
            fn (mixed $argument) => is_object($argument) ? spl_object_hash($argument) : $argument,
            $callable instanceof Closure ? (new ReflectionClosure($callable))->getClosureUsedVariables() : [],
        );

        return md5(sprintf(
            '%s@%s%s:%s (%s)',
            $trace[0]['file'],
            isset($trace[1]['class']) ? ($trace[1]['class'].'@') : '',
            $trace[1]['function'],
            $trace[0]['line'],
            serialize($uses),
        ));
    }
}
