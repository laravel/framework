<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Binary;
use InvalidArgumentException;

class AsBinary implements Castable
{
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes {
            private string $format;

            public function __construct(protected array $arguments)
            {
                $arguments = array_pad(array_values($this->arguments), 1, '');
                $this->format = $arguments[0] ?: throw new InvalidArgumentException('The binary format is required.');
                $allowedFormats = array_keys(Binary::formats());

                if (! in_array($this->format, $allowedFormats, true)) {
                    throw new InvalidArgumentException('The provided format [' . $this->format . '] is invalid.');
                }
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return;
                }

                return Binary::decode($attributes[$key], $this->format);
            }

            public function set($model, $key, $value, $attributes)
            {
                return [$key => Binary::encode($value, $this->format)];
            }
        };
    }
}
