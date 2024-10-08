<?php

namespace Illuminate\Database\Eloquent\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

class AsEnumCollection implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @template TEnum of \UnitEnum|\BackedEnum
     *
     * @param  array{class-string<TEnum>}  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\Collection<array-key, TEnum>, iterable<TEnum>>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            protected string $enumClass;
            protected bool $forceCollection;

            public function __construct(array $arguments)
            {
                $this->enumClass = $arguments[0];
                $this->forceCollection = $arguments[1] ?? false;
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return $this->forceCollection ? new Collection: null;
                }

                $data = Json::decode($attributes[$key]);

                if (! is_array($data)) {
                    return $this->forceCollection ? new Collection: null;
                }

                return (new Collection($data))->map(function ($value) {
                    return is_subclass_of($this->enumClass, BackedEnum::class)
                        ? $this->enumClass::from($value)
                        : constant($this->enumClass.'::'.$value);
                });
            }

            public function set($model, $key, $value, $attributes)
            {
                $value = $value !== null
                    ? Json::encode((new Collection($value))->map(function ($enum) {
                        return $this->getStorableEnumValue($enum);
                    })->jsonSerialize())
                    : null;

                return [$key => $value];
            }

            public function serialize($model, string $key, $value, array $attributes)
            {
                return (new Collection($value))->map(function ($enum) {
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
