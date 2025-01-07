<?php

namespace Illuminate\Database\Eloquent\Casts;

use DomainException;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class InRangeCastable implements Castable
{
    protected static $validTypes = [
        'int',
        'integer',
        'string',
    ];

    /**
     * @inheritDoc
     */
    public static function castUsing(array $arguments)
    {
        if (count($arguments) < 2) {
            throw new \InvalidArgumentException("InRangeCastable must be called with at least 2 arguments.");
        }

        $type = $arguments[0];
        if (!in_array($type, self::$validTypes)) {
            throw new \InvalidArgumentException("Cast value must be one of: ".implode(", ", self::$validTypes));
        }

        $min = $arguments[1];
        $max = $arguments[2];

        if (is_string($min)) {
            if (strlen(trim($min)) === 0) {
                $min = null;
            } else {
                $min = (int) $min;
            }
        }

        if (is_string($max)) {
            if (strlen(trim($max)) === 0) {
                $max = null;
            } else {
                $max = (int) $max;
            }
        }

        if (is_null($min) && is_null($max)) {
            throw new \InvalidArgumentException("You must specify at least one of min and max.");
        }

        return new class($type, $min, $max) implements CastsAttributes {
            public function __construct(
                protected string $type,
                protected int $min,
                protected int $max,
            ) {
            }

            public function get(Model $model, string $key, mixed $value, array $attributes)
            {
                return $value;
            }

            public function set(Model $model, string $key, mixed $value, array $attributes)
            {
                if (! $this->isValueInRange($value)) {
                    throw $this->buildException($key);
                }

                return $value;
            }

            private function buildException(string $key): DomainException
            {
                $type = $this->type === 'string' ? 'Length' : 'Value';

                return new DomainException("{$type} of key [$key] must be between $this->min and $this->max.");
            }

            private function isValueInRange($value): bool
            {
                $length = match ($this->type) {
                    'string' => mb_strlen($value),
                    'int', 'integer' => $value,
                };

                if (
                    (isset($this->min) && $length < $this->min)
                    || (isset($this->max) && $length > $this->max)) {
                    return false;
                }

                return true;
            }
        };
    }

    /**
     * @param  non-negative-int|null  $min
     * @param  positive-int|null  $max
     * @return string
     */
    public static function forString(int $min = null, int $max = null): string
    {
        return static::class.":string,{$min},{$max}";
    }

    /**
     * @param  non-negative-int|null  $min
     * @param  positive-int|null  $max
     * @return string
     */
    public static function forInteger(int $min = null, int $max = null): string
    {
        return static::class.":integer,{$min},{$max}";
    }
}
