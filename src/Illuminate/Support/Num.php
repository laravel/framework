<?php

namespace Illuminate\Support;

class Num
{
    /**
     * Decimal separator constants.
     */
    const POINT = '.';
    const COMMA = ',';

    /**
     * Convert a string to a float.
     *
     * @param  string  $value
     * @param  string|null  $decimalSeparator
     * @return float
     */
    public static function float(string $value, ?string $decimalSeparator = null): float
    {
        $decimalSeparator = $decimalSeparator ?? self::guessDecimalSeparator($value);
        $cleanedValue = preg_replace('/[^0-9' . preg_quote($decimalSeparator) . ']/', '', $value);

        if ($decimalSeparator === self::COMMA) {
            $floatValue = (float) str_replace($decimalSeparator, self::POINT, $cleanedValue);
        } else {
            $floatValue = (float) $cleanedValue;
        }

        return $floatValue;
    }

    /**
     * Convert a string to an integer.
     *
     * @param  string  $value
     * @param  string|null  $decimalSeparator
     * @return int
     */
    public static function int(string $value, ?string $decimalSeparator = null): int
    {
        return (int) self::float($value, $decimalSeparator);
    }

    /**
     * Guess the decimal separator from a string representing a number.
     *
     * @param  string  $value
     * @return string
     */
    public static function guessDecimalSeparator(string $value): string
    {
        $pointCount = substr_count($value, self::POINT);
        $commaCount = substr_count($value, self::COMMA);

        if ($pointCount == 0 && $commaCount == 0) {
            return self::POINT;
        }

        if ($pointCount > 0 && $commaCount == 0) {
            return ($pointCount > 1) ? self::COMMA : self::POINT;
        }

        if ($pointCount == 0 && $commaCount > 0) {
            return ($commaCount > 1) ? self::POINT : self::COMMA;
        }

        if ($pointCount < $commaCount) {
            return self::POINT;
        } elseif ($commaCount < $pointCount) {
            return self::COMMA;
        } else {
            $lastPointPosition = strrpos($value, self::POINT);
            $lastCommaPosition = strrpos($value, self::COMMA);

            if ($lastPointPosition !== false && $lastCommaPosition !== false) {
                return ($lastPointPosition > $lastCommaPosition) ? self::POINT : self::COMMA;
            } elseif ($lastPointPosition !== false) {
                return self::POINT;
            } elseif ($lastCommaPosition !== false) {
                return self::COMMA;
            }
        }

        return self::POINT;
    }
}
