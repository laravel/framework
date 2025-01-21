<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\PhoneNumber;
use InvalidArgumentException;

class AsE164PhoneNumber implements Castable
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

                $phone = PhoneNumber::of($value);

                if (! $phone->getCountry()) {
                    throw new InvalidArgumentException('Missing country specification for '.$key.' attribute cast');
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

                $countryField = $this->arguments[0] ?? $key.'_country';

                return $value
                    ->setCountry($attributes[$countryField] ?? $value->getCountry())
                    ->formatE164();
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
        return static::class.':'.$countryField;
    }
}
