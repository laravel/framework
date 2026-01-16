<?php

namespace Illuminate\Validation;

use BackedEnum;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException as BrickMathException;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Validation\CastsValidatedValue;
use Illuminate\Contracts\Validation\ValidationCastable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Exceptions\MathException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ValidationCaster
{
    /**
     * The primitive cast types supported from validated input.
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
        'double',
        'float',
        'immutable_date',
        'immutable_datetime',
        'int',
        'integer',
        'real',
        'string',
    ];

    /**
     * The cast types that are not supported from validated input.
     *
     * @var list<string>
     */
    protected static $unsupportedCastTypes = [
        'encrypted',
        'encrypted:array',
        'encrypted:collection',
        'encrypted:json',
        'encrypted:object',
        'hashed',
        'object',
        'timestamp',
    ];

    /**
     * The paths that have already been cast.
     *
     * @var array<string, true>
     */
    protected $processedPaths = [];

    /**
     * Apply casts to the validated data.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<string, string|\Illuminate\Contracts\Validation\CastsValidatedValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string>  $casts
     * @return array<string, mixed>
     */
    public function apply(array $validated, array $casts)
    {
        $result = $validated;
        $this->processedPaths = [];

        // Sort casts by specificity (exact paths before wildcards).
        uksort($casts, fn ($a, $b) => substr_count($a, '*') <=> substr_count($b, '*'));

        foreach ($casts as $key => $cast) {
            $result = $this->applyCastToPath($result, $key, $cast);
        }

        return $result;
    }

    /**
     * Apply a cast to all matching paths.
     *
     * @param  array<string, mixed>  $data
     * @param  string|\Illuminate\Contracts\Validation\CastsValidatedValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
     * @return array<string, mixed>
     */
    protected function applyCastToPath(array $data, string $path, mixed $cast)
    {
        if (str_contains($path, '*')) {
            return $this->applyWildcardCast($data, $path, $cast);
        }

        if (! isset($this->processedPaths[$path]) && Arr::has($data, $path)) {
            $value = Arr::get($data, $path);

            Arr::set($data, $path, $this->castValue($value, $path, $cast, $data));
            $this->processedPaths[$path] = true;
        }

        return $data;
    }

    /**
     * Cast a single value.
     *
     * @param  string|\Illuminate\Contracts\Validation\CastsValidatedValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
     * @param  array<string, mixed>  $validated
     * @return mixed
     */
    protected function castValue(mixed $value, string $key, mixed $cast, array $validated)
    {
        if ($value === null) {
            return null;
        }

        $caster = $this->resolveCaster($cast);

        if ($caster instanceof CastsValidatedValue) {
            return $caster->cast($value, $key, $validated);
        }

        if ($caster instanceof CastsAttributes) {
            return $caster->get(null, $key, $value, $validated);
        }

        return $this->castPrimitive($value, $cast);
    }

    /**
     * Resolve a cast specification to a caster.
     *
     * @param  string|\Illuminate\Contracts\Validation\CastsValidatedValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
     * @return \Illuminate\Contracts\Validation\CastsValidatedValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|string
     *
     * @throws \Illuminate\Validation\InvalidCastException
     */
    protected function resolveCaster(mixed $cast)
    {
        if ($cast instanceof CastsValidatedValue || $cast instanceof CastsAttributes) {
            return $cast;
        }

        if (is_string($cast)) {
            $baseCast = Str::before($cast, ':') ?: $cast;

            if (in_array($baseCast, static::$unsupportedCastTypes) || str_starts_with($cast, 'encrypted')) {
                throw new InvalidCastException(
                    "Cast type '{$baseCast}' is not supported for validation. This cast is specific to Eloquent models."
                );
            }

            if (in_array($baseCast, static::$primitiveCastTypes)) {
                return $cast;
            }

            if (class_exists($cast) || class_exists($baseCast)) {
                return $this->resolveClassCaster($cast);
            }
        }

        throw new InvalidCastException("Invalid cast specification: {$cast}");
    }

    /**
     * Resolve a class-based caster.
     *
     * @param  class-string|string  $cast
     * @return \Illuminate\Contracts\Validation\CastsValidatedValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes
     */
    protected function resolveClassCaster(string $cast)
    {
        [$class, $arguments] = $this->parseCastArguments($cast);

        if (is_subclass_of($class, ValidationCastable::class)) {
            $caster = $class::castUsing($arguments);

            return is_string($caster) ? new $caster : $caster;
        }

        if (is_subclass_of($class, Castable::class)) {
            $caster = $class::castUsing($arguments);

            return is_string($caster) ? new $caster : $caster;
        }

        if (enum_exists($class)) {
            return $this->createEnumCaster($class);
        }

        if (is_subclass_of($class, CastsValidatedValue::class)) {
            return empty($arguments) ? new $class : new $class(...$arguments);
        }

        if (is_subclass_of($class, CastsAttributes::class)) {
            return empty($arguments) ? new $class : new $class(...$arguments);
        }

        throw new InvalidCastException(
            "Class {$class} must implement CastsValidatedValue, ValidationCastable, or be an enum."
        );
    }

    /**
     * Create an enum caster.
     *
     * @param  class-string<\UnitEnum>  $enumClass
     * @return \Illuminate\Contracts\Validation\CastsValidatedValue<\UnitEnum>
     */
    protected function createEnumCaster(string $enumClass)
    {
        return new class($enumClass) implements CastsValidatedValue
        {
            public function __construct(protected string $enumClass)
            {
            }

            public function cast(mixed $value, string $key, array $attributes)
            {
                if ($value instanceof $this->enumClass) {
                    return $value;
                }

                return is_subclass_of($this->enumClass, BackedEnum::class)
                    ? ($this->enumClass)::from($value)
                    : constant($this->enumClass.'::'.$value);
            }
        };
    }

    /**
     * Cast a value using primitive cast rules.
     *
     * @return int|bool|float|string|array<array-key, mixed>|\Illuminate\Support\Collection<array-key, mixed>|\Illuminate\Support\Carbon|\Carbon\CarbonImmutable
     *
     * @throws \Illuminate\Validation\InvalidCastException
     */
    protected function castPrimitive(mixed $value, string $cast)
    {
        [$type, $parameters] = $this->parseCastArguments($cast);

        return match ($type) {
            'int', 'integer' => (int) $value,
            'bool', 'boolean' => $this->asBoolean($value),
            'float', 'double', 'real' => (float) $value,
            'string' => (string) $value,
            'array' => $this->asArray($value),
            'collection' => $this->asCollection($value),
            'date' => $this->asDate($value, $parameters[0] ?? null),
            'datetime' => $this->asDateTime($value, $parameters[0] ?? null),
            'immutable_date' => $this->asDate($value, $parameters[0] ?? null, true),
            'immutable_datetime' => $this->asDateTime($value, $parameters[0] ?? null, true),
            'decimal' => $this->asDecimal($value, (int) ($parameters[0] ?? 2)),
            default => throw new InvalidCastException("Unknown primitive cast type: {$type}"),
        };
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
     * Cast a value to a Collection.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Collection<array-key, mixed>
     */
    protected function asCollection($value)
    {
        return new Collection($this->asArray($value));
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
    protected function asDecimal($value, $decimals)
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
    protected function asDate($value, $format = null, $immutable = false)
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
    protected function asDateTime($value, $format = null, $immutable = false)
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
     * Apply cast using wildcard path matching.
     *
     * @param  array<string, mixed>  $data
     * @param  string|\Illuminate\Contracts\Validation\CastsValidatedValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
     * @return array<string, mixed>
     */
    protected function applyWildcardCast(array $data, string $pattern, mixed $cast)
    {
        $paths = $this->expandWildcardPath($data, $pattern);

        foreach ($paths as $path) {
            if (isset($this->processedPaths[$path])) {
                continue;
            }

            if (Arr::has($data, $path)) {
                $value = Arr::get($data, $path);

                Arr::set($data, $path, $this->castValue($value, $path, $cast, $data));
                $this->processedPaths[$path] = true;
            }
        }

        return $data;
    }

    /**
     * Expand a wildcard pattern to all matching concrete paths in the data.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    protected function expandWildcardPath(array $data, string $pattern)
    {
        $paths = new Collection(['']);

        foreach (explode('.', $pattern) as $segment) {
            $paths = $paths->flatMap(function ($path) use ($data, $segment) {
                if ($segment !== '*') {
                    return [$path === '' ? $segment : "{$path}.{$segment}"];
                }

                $current = $path === '' ? $data : Arr::get($data, $path);

                return is_array($current)
                    ? (new Collection($current))->keys()->map(fn ($key) => $path === '' ? (string) $key : "{$path}.{$key}")
                    : [];
            });
        }

        return $paths->all();
    }

    /**
     * Parse cast string into type and arguments.
     *
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
}
