<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use ValueError;

class AsInstance implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\Collection<array-key, mixed>, iterable>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            protected string $class;

            public function __construct(array $arguments)
            {
                $this->class = $arguments[0]
                    ?? throw new InvalidArgumentException('A class name must be provided to cast as an instance.');
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

                if (method_exists($this->class, 'fromArray')) {
                    return $this->class::fromArray($data);
                }

                return new $this->class($data);
            }

            public function set($model, $key, $value, $attributes)
            {
                if (! is_null($value)) {
                    return [
                        $key => match(true) {
                            $value instanceof Jsonable => $value->toJson(),
                            $value instanceof Arrayable => Json::encode($value->toArray()),
                            default => throw new ValueError(sprintf(
                                    'The %s class should implement Jsonable or Arrayable contract.', $this->class)
                            )
                        }
                    ];
                }

                return null;
            }
        };
    }

    /**
     * Specify the class to make an instance from.
     *
     * @param  class-string  $class
     * @return string
     */
    public static function of($class)
    {
        return static::class . ':' . $class;
    }
}
