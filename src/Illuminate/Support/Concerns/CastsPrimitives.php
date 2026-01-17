<?php

namespace Illuminate\Support\Concerns;

use BackedEnum;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException as BrickMathException;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Exceptions\MathException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\InvalidCastException;
use InvalidArgumentException;
use stdClass;

trait CastsPrimitives
{
    /**
     * The primitive cast types.
     *
     * @var list<string>
     */
    protected static $primitiveCastTypes = [
        'array',
        'bool',
        'boolean',
        'collection',
        'date',
        'datetime',
        'decimal',
        'decrypted',
        'double',
        'encrypted',
        'float',
        'hashed',
        'immutable_date',
        'immutable_datetime',
        'int',
        'integer',
        'object',
        'real',
        'string',
        'timestamp',
    ];

    /**
     * Determine if the given cast type is a primitive.
     *
     * @param  string  $cast
     * @return bool
     */
    protected function isPrimitiveCast(string $cast)
    {
        return in_array(Str::before($cast, ':'), static::$primitiveCastTypes);
    }

    /**
     * Cast a value using primitive cast rules.
     *
     * @param  mixed  $value
     * @param  string  $cast
     * @return mixed
     *
     * @throws \Illuminate\Validation\InvalidCastException
     */
    protected function castPrimitive(mixed $value, string $cast)
    {
        [$type, $parameters] = $this->parseCastArguments($cast);

        return match ($type) {
            'int', 'integer' => $this->asInteger($value),
            'bool', 'boolean' => $this->asBoolean($value),
            'float', 'double', 'real' => $this->asFloat($value),
            'string' => $this->asString($value),
            'array' => $this->asArray($value),
            'object' => $this->asObject($value),
            'collection' => new Collection($this->asArray($value)),
            'date' => $this->asDate($value, $parameters[0] ?? null),
            'datetime' => $this->asDateTime($value, $parameters[0] ?? null),
            'immutable_date' => $this->asDate($value, $parameters[0] ?? null, true),
            'immutable_datetime' => $this->asDateTime($value, $parameters[0] ?? null, true),
            'timestamp' => $this->asTimestamp($value),
            'decimal' => $this->asDecimal($value, (int) ($parameters[0] ?? 2)),
            'encrypted' => $this->asEncrypted($value, $parameters[0] ?? null),
            'decrypted' => $this->asDecrypted($value, $parameters[0] ?? null),
            'hashed' => $this->asHashed($value),
            default => throw new InvalidCastException("Unknown primitive cast type: {$type}"),
        };
    }

    /**
     * Parse cast string into type and arguments.
     *
     * @param  string  $cast
     * @return array{0: string, 1: list<string>}
     */
    protected function parseCastArguments(string $cast)
    {
        if (! str_contains($cast, ':')) {
            return [$cast, []];
        }

        [$type, $args] = explode(':', $cast, 2);

        return [$type, explode(',', $args)];
    }

    /**
     * Cast a value to a boolean.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function asBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }

    /**
     * Cast a value to an integer.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asInteger($value)
    {
        return (int) $value;
    }

    /**
     * Cast a value to a float.
     *
     * @param  mixed  $value
     * @return float
     */
    protected function asFloat($value)
    {
        return match ((string) $value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float) $value,
        };
    }

    /**
     * Cast a value to a string.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function asString($value)
    {
        return (string) $value;
    }

    /**
     * Cast a value to an array.
     *
     * @param  mixed  $value
     * @return array<array-key, mixed>
     */
    protected function asArray($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        return json_decode($value, true) ?? [];
    }

    /**
     * Cast a value to an object.
     *
     * @param  mixed  $value
     * @return object
     */
    protected function asObject($value)
    {
        if (is_object($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return new stdClass;
        }

        return json_decode($value, false) ?? new stdClass;
    }

    /**
     * Return a decimal as a string.
     *
     * @param  mixed  $value
     * @param  int  $decimals
     * @return string
     *
     * @throws \Illuminate\Support\Exceptions\MathException
     */
    protected function asDecimal($value, int $decimals)
    {
        try {
            return (string) BigDecimal::of($value)->toScale($decimals, RoundingMode::HALF_UP);
        } catch (BrickMathException $e) {
            throw new MathException('Unable to cast value to a decimal.', previous: $e);
        }
    }

    /**
     * Return a date as DateTime object with time set to 00:00:00.
     *
     * @param  mixed  $value
     * @param  string|null  $format
     * @param  bool  $immutable
     * @return \Illuminate\Support\Carbon|\Carbon\CarbonImmutable
     */
    protected function asDate($value, ?string $format = null, bool $immutable = false)
    {
        return $this->asDateTime($value, $format, $immutable)->startOfDay();
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @param  string|null  $format
     * @param  bool  $immutable
     * @return \Illuminate\Support\Carbon|\Carbon\CarbonImmutable
     */
    protected function asDateTime($value, ?string $format = null, bool $immutable = false)
    {
        if ($value instanceof CarbonInterface) {
            return $immutable ? $value->toImmutable() : Date::instance($value);
        }

        if ($value instanceof DateTimeInterface) {
            $date = Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );

            return $immutable ? $date->toImmutable() : $date;
        }

        if (is_numeric($value)) {
            $date = Date::createFromTimestamp($value, date_default_timezone_get());

            return $immutable ? $date->toImmutable() : $date;
        }

        if ($this->isStandardDateFormat($value)) {
            $date = Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());

            return $immutable ? $date->toImmutable() : $date;
        }

        if ($format) {
            try {
                $date = Date::createFromFormat($format, $value);
            } catch (InvalidArgumentException) {
                $date = false;
            }

            if ($date !== false) {
                return $immutable ? $date->toImmutable() : $date;
            }
        }

        $date = Date::parse($value);

        return $immutable ? $date->toImmutable() : $date;
    }

    /**
     * Return a timestamp as unix timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asTimestamp($value)
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Determine if the given value is a standard date format.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isStandardDateFormat($value)
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    /**
     * Cast a value to an enum instance.
     *
     * @template TEnum of \UnitEnum
     *
     * @param  mixed  $value
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum
     */
    protected function asEnum($value, string $enumClass)
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        return is_subclass_of($enumClass, BackedEnum::class)
            ? $enumClass::from($value)
            : constant($enumClass.'::'.$value);
    }

    /**
     * Encrypt a value with optional subtype.
     *
     * @param  mixed  $value
     * @param  string|null  $subtype
     * @return string
     */
    protected function asEncrypted($value, ?string $subtype = null)
    {
        $value = match ($subtype) {
            'array', 'json', 'collection', 'object' => json_encode($value),
            default => $value,
        };

        return Crypt::encryptString($value);
    }

    /**
     * Decrypt an encrypted value with optional subtype.
     *
     * @param  mixed  $value
     * @param  string|null  $subtype
     * @return mixed
     */
    protected function asDecrypted($value, ?string $subtype = null)
    {
        $decrypted = Crypt::decryptString($value);

        return match ($subtype) {
            'array', 'json' => json_decode($decrypted, true) ?? [],
            'collection' => new Collection(json_decode($decrypted, true) ?? []),
            'object' => json_decode($decrypted, false) ?? new stdClass,
            default => $decrypted,
        };
    }

    /**
     * Hash a value if not already hashed.
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function asHashed($value)
    {
        if ($value === null) {
            return null;
        }

        if (Hash::isHashed($value)) {
            return $value;
        }

        return Hash::make($value);
    }
}
