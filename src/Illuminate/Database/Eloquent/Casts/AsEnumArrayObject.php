<?php

namespace Illuminate\Database\Eloquent\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

class AsEnumArrayObject implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @template TEnum
     *
     * @param  array{class-string<TEnum>}  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Database\Eloquent\Casts\ArrayObject<array-key, TEnum>, iterable<TEnum>>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            protected string $enumClass;
            protected bool $forceInstance;

            public function __construct(array $arguments)
            {
                $this->enumClass = $arguments[0];
                $this->forceInstance = $arguments[1] ?? false;
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return $this->forceInstance ? new ArrayObject : null;
                }

                $data = Json::decode($attributes[$key]);

                if (! is_array($data)) {
                    return $this->forceInstance ? new ArrayObject : null;
                }

                return new ArrayObject((new Collection($data))->map(function ($value) {
                    return is_subclass_of($this->enumClass, BackedEnum::class)
                        ? $this->enumClass::from($value)
                        : constant($this->enumClass.'::'.$value);
                })->toArray());
            }

            public function set($model, $key, $value, $attributes)
            {
                if ($value === null) {
                    return [$key => null];
                }

                $storable = [];

                foreach ($value as $enum) {
                    $storable[] = $this->getStorableEnumValue($enum);
                }

                return [$key => Json::encode($storable)];
            }

            public function serialize($model, string $key, $value, array $attributes)
            {
                return (new Collection($value->getArrayCopy()))->map(function ($enum) {
                    return $this->getStorableEnumValue($enum);
                })->toArray();
            }

            protected function getStorableEnumValue($enum)
            {
                if (is_string($enum) || is_int($enum)) {
                    return $enum;
                }

                return $enum instanceof BackedEnum ? $enum->value : $enum->name;
            }
        };
    }

    /**
     * Specify the Enum for the cast.
     *
     * @param  class-string  $class
     * @param  bool  $force
     * @return string
     */
    public static function of(string $class, bool $force = false): string
    {
        if ($force) {
            return static::class.':'.$class.',force';
        }

        return static::class.':'.$class;
    }
}
