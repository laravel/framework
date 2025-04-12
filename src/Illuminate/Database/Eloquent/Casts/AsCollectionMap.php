<?php

namespace Illuminate\Database\Eloquent\Casts;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AsCollectionMap implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @template TValue
     *
     * @param  array{0: class-string<TValue>|(callable(mixed):TValue), 1?: string}  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\Collection<array-key, TValue>, iterable<TValue>>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            protected $callable;

            public function __construct(array $arguments)
            {
                $arguments = array_values($arguments);

                if (empty($arguments) || empty($arguments[0])) {
                    throw new InvalidArgumentException('No class or callable has been set to map the Collection.');
                }

                if (isset($arguments[1]) && ! is_array($arguments[0])) {
                    $arguments = [$arguments[0], $arguments[1]];
                    unset($arguments[1]);
                }

                $this->callable = $arguments[0];
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return;
                }

                $data = Json::decode($attributes[$key]);

                if (! is_array($data)) {
                    return null;
                }

                if (is_callable($this->callable)) {
                    return Collection::make($data)->map($this->callable);
                }

                [$class, $method] = Str::parseCallback($this->callable);

                return $method
                    ? Collection::make($data)->map([$class, $method])
                    : Collection::make($data)->mapInto($class);
            }

            public function set($model, $key, $value, $attributes)
            {
                return [$key => Json::encode($value)];
            }
        };
    }

    /**
     * Specify the class to map into each item in the Collection cast.
     *
     * @param  class-string  $class
     * @return string
     */
    public static function into($class)
    {
        return static::class.':'.$class;
    }

    /**
     * Specify the callable to map each item in the Collection cast.
     *
     * @param  callable-string|array{0: class-string, 1: string}  $callback
     * @param  string|null  $method
     * @return string
     */
    public static function using($callback, $method = null)
    {
        if ($callback instanceof Closure) {
            throw new InvalidArgumentException('The provided callback should be a callable array or string.');
        }

        if (is_array($callback) && is_callable($callback)) {
            [$callback, $method] = [$callback[0], $callback[1]];
        }

        return $method === null
            ? static::class.':'.$callback
            : static::class.':'.$callback.'@'.$method;
    }
}
