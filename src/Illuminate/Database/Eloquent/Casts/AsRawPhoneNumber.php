<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\PhoneNumber;
use InvalidArgumentException;
use Illuminate\Contracts\Database\Eloquent\Castable;

class AsRawPhoneNumber implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\PhoneNumber>
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
                if (! $value) {
                    return null;
                }

                $countryField = $this->arguments[0] ?? $key . '_country';

                $phone = PhoneNumber::of($value, $countryField);

                if (! $phone->getCountry()) {
                    throw new InvalidArgumentException('Missing country specification for ' . $key . ' attribute cast');
                }

                return $phone;
            }

            public function set($model, $key, $value, $attributes)
            {
                if (! $value) {
                    return null;
                }

                if (is_string($value)) {
                    $value = PhoneNumber::of($value);
                }

                return $value->getRawNumber();
            }

            public function serialize($model, string $key, $value, array $attributes)
            {
                if (! $value) {
                    return null;
                }

                return $value->getRawNumber();
            }
        };
    }

    /**
     * Specify the country field for the cast.
     *
     * @param  string  $countryField
     * @return string
     */
    public static function of($countryField)
    {
        return static::class . ':' . $countryField;
    }
}
