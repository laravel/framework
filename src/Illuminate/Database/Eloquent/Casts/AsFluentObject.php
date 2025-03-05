<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsFluentObject implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\Fluent>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            public function __construct(protected array $arguments)
            {
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return;
                }

                $data = Json::decode($attributes[$key]);

                if (! is_array($data)) {
                    return;
                }

                $fluentClass = $this->arguments[0] ?? null;
                if (is_subclass_of($fluentClass, \Illuminate\Support\Fluent::class) === false) {
                    $fluentClass = \Illuminate\Support\Fluent::class;
                }

                return new $fluentClass($data);
            }

            public function set($model, $key, $value, $attributes)
            {
                return [$key => Json::encode($value)];
            }
        };
    }

    public static function using($class)
    {
        return static::class.':'.$class;
    }
}
