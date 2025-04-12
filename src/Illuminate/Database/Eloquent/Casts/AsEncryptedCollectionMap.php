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
            public function __construct(protected array $arguments)
            {
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return;
                }

                $data = Json::decode(Crypt::decryptString($attributes[$key]));

                if (!is_array($data)) {
                    return null;
                }

                $this->arguments[0] ??= '';

                if (is_callable($this->arguments[0])) {
                    return Collection::make($data)->map($this->arguments[0]);
                }

                [$class, $method] = Str::parseCallback($this->arguments[0]);

                if ($method) {
                    return Collection::make($data)->map([$class, $method]);
                }

                if ($class) {
                    return Collection::make($data)->mapInto($class);
                }

                throw new InvalidArgumentException('No class or callable has been set to map the Collection.');
            }

            public function set($model, $key, $value, $attributes)
            {
                if (! is_null($value)) {
                    return [$key => Crypt::encryptString(Json::encode($value))];
                }

                return null;
            }
        };
    }
}
