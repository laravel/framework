<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AsEncryptedCollectionMap extends AsCollectionMap
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @template TValue
     *
     * @param  array{class-string<TValue>|(callable(mixed):TValue)}  $arguments
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

                if (isset($arguments[1]) && ! is_array($arguments[1])) {
                    $arguments = [$arguments[0], $arguments[1]];
                    unset($arguments[1]);
                }

                $this->callable = $arguments[0];
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return null;
                }

                $data = Json::decode(Crypt::decryptString($attributes[$key]));

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
                return is_null($value)
                    ? null
                    : [$key => Crypt::encryptString(Json::encode($value))];
            }
        };
    }
}
