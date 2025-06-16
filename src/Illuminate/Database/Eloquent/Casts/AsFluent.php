<?php

namespace Illuminate\Database\Eloquent\Casts;

use BadMethodCallException;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Fluent;
use InvalidArgumentException;

class AsFluent implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\Fluent, string>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            public function __construct(protected array $arguments)
            {
                $this->arguments = array_pad(array_values($this->arguments), 2, '');
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return null;
                }

                $fluentClass = empty($this->arguments[0]) ? Fluent::class : $this->arguments[0];

                throw_unless(
                    is_a($fluentClass, Fluent::class, true), 
                    new InvalidArgumentException('The provided class must extend ['.Fluent::class.'].')
                );

                $data = Json::decode($attributes[$key]);

                $instance = new $fluentClass($data);

                $method = $this->arguments[1];

                if (! isset($this->arguments[1]) || ! $this->arguments[1]) {
                    return $instance;
                }

                throw_unless(is_string($method), new InvalidArgumentException('Method name must be a string.'));

                throw_if(! method_exists($instance, $method) || ! is_callable([$instance, $method]), new BadMethodCallException("Method {$method} does not exist or is not callable."));

                return $instance->$method();
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? [$key => Json::encode($value)] : null;
            }
        };
    }

    /**
     * Specify the collection type for the cast.
     *
     * @param  class-string  $class
     */
    public static function using(string $class, ?string $method = null): string
    {
        return self::class.':'.implode(',', func_get_args());
    }
}
