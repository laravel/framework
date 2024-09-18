<?php

namespace Illuminate\Support;

/**
 * @template  TReturnType
 * @template  TRecursionCallable of callable(): TReturnType
 * @template  TRecursionType of TReturnType|TRecursionCallable
 *
 * @property-read  callable(): TReturnType  $callback
 * @property-read  TRecursionType  $onRecursion
 * @property-read  object|null $object
 */
class Recursable
{
    public const BLANK_SIGNATURE = 'unknown target';

    public readonly string $signature;
    public readonly string $hash;

    public function __construct(
        protected $callback,
        protected $onRecursion,
        protected object|null $object,
        ?string $signature = null,
        ?string $hash = null,
    ) {
        $this->signature = $signature ?: static::BLANK_SIGNATURE;
        $this->hash = $hash ?: static::hashFromSignature($this->signature);
    }

    /**
     * Read-only access to the properties of the recursable.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return property_exists($this, $name) ? $this->{$name} : null;
    }

    /**
     * Set the object of the recursable if it is not already set.
     *
     * @param  object  $object
     * @return $this
     */
    public function for(object $object): static
    {
        $this->object ??= $object;

        return $this;
    }

    /**
     * Set the value to return when recursing.
     *
     * @param  TRecursionType  $value
     * @return $this
     */
    public function return(mixed $value): static
    {
        $this->onRecursion = $value;

        return $this;
    }

    /**
     * Creates a new recursable instance from the given trace.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @param  callable(): TReturnType  $callback
     * @param  TRecursionType  $onRecursion
     * @param  object|null  $object
     * @return static
     */
    public static function fromTrace(
        array $trace,
        callable $callback,
        mixed $onRecursion,
        ?object $object = null,
    ): static {
        return new static(
            $callback,
            $onRecursion,
            $object ?? static::objectFromTrace($trace),
            static::signatureFromTrace($trace),
            static::hashFromTrace($trace),
        );
    }

    /**
     * Creates a new recursable instance from a given signature.
     *
     * @param  string  $signature
     * @param  callable(): TReturnType  $callback
     * @param  TRecursionType  $onRecursion
     * @param  object|null  $object
     * @return static
     */
    public static function fromSignature(
        string $signature,
        callable $callback,
        mixed $onRecursion,
        ?object $object = null,
    ): static {
        return new static(
            $callback,
            $onRecursion,
            $object,
            $signature,
            static::hashFromSignature($signature ?: static::BLANK_SIGNATURE),
        );
    }

    /**
     * Computes the target method from the given trace, if any.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @return array{file: string, class: string, function: string, line: int, object: object|null}
     */
    protected static function targetFromTrace(array $trace): array
    {
        return [
            'file' => $trace[0]['file'] ?? '',
            'class' => $trace[1]['class'] ?? '',
            'function' => $trace[1]['function'] ?? '',
            'line' => $trace[0]['line'] ?? 0,
            'object' => $trace[1]['object'] ?? null,
        ];
    }

    /**
     * Computes the object of the recursable from the given trace, if any.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @return object|null
     */
    protected static function objectFromTrace(array $trace): object|null
    {
        return static::targetFromTrace($trace)['object'];
    }

    /**
     * Computes the signature of the recursable.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @return string
     */
    protected static function signatureFromTrace(array $trace): string
    {
        $target = static::targetFromTrace($trace);

        return sprintf(
            '%s:%s%s',
            $target['file'],
            $target['class'] ? ($target['class'].'@') : '',
            $target['function'] ?: $target['line'],
        );
    }

    /**
     * Computes the hash of the recursable from the given trace.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @return string
     */
    protected static function hashFromTrace(array $trace): string
    {
        return static::hashFromSignature(static::signatureFromTrace($trace));
    }

    /**
     * Computes the hash of the recursable from the given signature.
     *
     * @param  string  $signature
     * @return string
     */
    protected static function hashFromSignature(string $signature): string
    {
        return hash('xxh128', $signature);
    }
}
