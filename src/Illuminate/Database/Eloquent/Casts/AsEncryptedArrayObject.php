<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

class AsEncryptedArrayObject implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Database\Eloquent\Casts\ArrayObject<array-key, mixed>, iterable>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            protected bool $forceInstance;

            public function __construct(array $arguments)
            {
                $this->forceInstance = ($arguments[0] ?? '') === 'force';
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return $this->defaultValue();
                }

                $data = Json::decode(Crypt::decryptString($attributes[$key]));

                if (! is_array($data)) {
                    return $this->defaultValue();
                }

                return new ArrayObject($data);
            }

            public function set($model, $key, $value, $attributes)
            {
                if (! is_null($value)) {
                    return [$key => Crypt::encryptString(Json::encode($value))];
                }

                return null;
            }

            public function serialize($model, string $key, $value, array $attributes)
            {
                return ! is_null($value) ? $value->getArrayCopy() : null;
            }

            protected function defaultValue(): ?ArrayObject
            {
                return $this->forceInstance ? new ArrayObject : null;
            }
        };
    }

    /**
     * Always get a ArrayObject instance.
     */
    public static function force(): string
    {
        return static::class.':force';
    }
}
