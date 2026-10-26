<?php

namespace Illuminate\Database\Eloquent\Casts;

use BcMath\Number;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException as BrickMathException;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Exceptions\MathException;
use RuntimeException;

class AsNumber implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array{0?: string}  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\BcMath\Number, \BcMath\Number|string|int|float>
     *
     * @throws \RuntimeException
     */
    public static function castUsing(array $arguments)
    {
        if (! class_exists(Number::class)) {
            throw new RuntimeException('The [bcmath] extension is required to use the ['.static::class.'] cast.');
        }

        return new class(isset($arguments[0]) ? (int) $arguments[0] : null) implements CastsAttributes, ComparesCastableAttributes, SerializesCastableAttributes
        {
            /**
             * Return a fresh number on every access so assigned values are always read back at the cast's scale.
             */
            public bool $withoutObjectCaching = true;

            public function __construct(protected ?int $scale)
            {
            }

            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? $this->toNumber($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $this->toNumber($value) : null;
            }

            public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue)
            {
                if (is_null($firstValue) || is_null($secondValue)) {
                    return $firstValue === $secondValue;
                }

                return $this->toNumber($firstValue)->compare($this->toNumber($secondValue)) === 0;
            }

            public function serialize(Model $model, string $key, mixed $value, array $attributes)
            {
                return isset($value) ? (string) $value : null;
            }

            /**
             * Convert the given value to a number, rounding it to the configured scale.
             *
             * @throws \Illuminate\Support\Exceptions\MathException
             */
            protected function toNumber(mixed $value): Number
            {
                try {
                    $decimal = BigDecimal::of((string) $value);

                    if (! is_null($this->scale)) {
                        $decimal = $decimal->toScale($this->scale, RoundingMode::HalfUp);
                    }

                    return new Number((string) $decimal);
                } catch (BrickMathException $e) {
                    throw new MathException('Unable to cast value to a number.', previous: $e);
                }
            }
        };
    }

    /**
     * Cast the attribute to a number with the given scale.
     */
    public static function of(int $scale): string
    {
        return self::class.':'.$scale;
    }
}
