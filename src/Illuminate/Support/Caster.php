<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\CastsValue;
use Illuminate\Support\Concerns\CastsPrimitives;
use Illuminate\Support\Exceptions\InvalidCastException;

class Caster
{
    use CastsPrimitives;

    /**
     * The global date format.
     *
     * @var string|null
     */
    protected static $globalDateFormat;

    /**
     * The cast definitions.
     *
     * @var array<string, string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string>
     */
    protected $casts = [];

    /**
     * The instance date format.
     *
     * @var string|null
     */
    protected $instanceDateFormat;

    /**
     * The paths that have already been cast.
     *
     * @var array<string, true>
     */
    protected $processedPaths = [];

    /**
     * The resolved caster instances cache.
     *
     * @var array<string|int, \Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes>
     */
    protected $resolvedCasters = [];

    /**
     * Create a new Caster instance.
     *
     * @param  array<string, string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string>  $casts
     * @return void
     */
    public function __construct(array $casts = [])
    {
        $this->casts = $casts;
    }

    /**
     * Create a new Caster instance.
     *
     * @param  array<string, string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string>  $casts
     * @return static
     */
    public static function make(array $casts = [])
    {
        return new static($casts);
    }

    /**
     * Set or merge cast definitions.
     *
     * @param  array<string, string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string>  $casts
     * @return $this
     */
    public function casts(array $casts)
    {
        $this->casts = array_merge($this->casts, $casts);

        return $this;
    }

    /**
     * Get the cast definitions.
     *
     * @return array<string, string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string>
     */
    public function getCasts()
    {
        return $this->casts;
    }

    /**
     * Set the global date format.
     *
     * @param  string|null  $format
     * @return void
     */
    public static function useDateFormat(?string $format)
    {
        static::$globalDateFormat = $format;
    }

    /**
     * Set the instance date format.
     *
     * @param  string|null  $format
     * @return $this
     */
    public function dateFormat(?string $format)
    {
        $this->instanceDateFormat = $format;

        return $this;
    }

    /**
     * Get the date format for parsing.
     *
     * @return string|null
     */
    protected function getDateFormat()
    {
        return $this->instanceDateFormat ?? static::$globalDateFormat;
    }

    /**
     * Cast the given data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function cast(array $data)
    {
        $result = $data;
        $this->processedPaths = [];
        $this->resolvedCasters = [];

        $casts = $this->casts;

        uksort($casts, fn ($a, $b) => substr_count($a, '*') <=> substr_count($b, '*'));

        foreach ($casts as $key => $cast) {
            $result = $this->applyCastToPath($result, $key, $cast);
        }

        return $result;
    }

    /**
     * Get a single casted value from the data.
     *
     * @param  array<string, mixed>  $data
     * @return mixed
     */
    public function get(array $data, string $key, mixed $default = null)
    {
        $casted = $this->cast($data);

        return data_get($casted, $key, $default);
    }

    /**
     * Get a subset of casted values.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function only(array $data, array $keys)
    {
        $casted = $this->cast($data);

        return Arr::only($casted, $keys);
    }

    /**
     * Cast a single value.
     *
     * @param  string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
     * @return mixed
     */
    public static function value(mixed $value, mixed $cast)
    {
        return (new static)->castValue($value, '', $cast, []);
    }

    /**
     * Apply a cast to all matching paths.
     *
     * @param  array<string, mixed>  $data
     * @param  string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
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
     * @param  string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
     * @param  array<string, mixed>  $data
     * @return mixed
     */
    protected function castValue(mixed $value, string $key, mixed $cast, array $data)
    {
        if ($value === null) {
            return null;
        }

        $caster = $this->resolveCaster($cast);

        if ($caster instanceof CastsValue) {
            return $caster->cast($value, $key, $data);
        }

        if ($caster instanceof CastsAttributes) {
            return $caster->get(null, $key, $value, $data);
        }

        return $this->castPrimitive($value, $cast);
    }

    /**
     * Resolve a cast specification to a caster.
     *
     * @param  string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
     * @return \Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|string
     *
     * @throws \Illuminate\Validation\InvalidCastException
     */
    protected function resolveCaster(mixed $cast)
    {
        if ($cast instanceof CastsValue || $cast instanceof CastsAttributes) {
            return $cast;
        }

        if (is_string($cast)) {
            if ($this->isPrimitiveCast($cast)) {
                return $cast;
            }

            $baseCast = Str::before($cast, ':');

            if (class_exists($cast) || class_exists($baseCast)) {
                return $this->resolvedCasters[$cast] ?? ($this->resolvedCasters[$cast] = $this->resolveClassCaster($cast));
            }
        }

        throw new InvalidCastException("Invalid cast specification: {$cast}");
    }

    /**
     * Resolve a class-based caster.
     *
     * @param  class-string|string  $cast
     * @return \Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes
     *
     * @throws \Illuminate\Validation\InvalidCastException
     */
    protected function resolveClassCaster(string $cast)
    {
        [$class, $arguments] = $this->parseCastArguments($cast);

        if (is_subclass_of($class, Castable::class)) {
            $caster = $class::castUsing($arguments);

            return is_string($caster) ? new $caster : $caster;
        }

        if (enum_exists($class)) {
            return $this->createEnumCaster($class);
        }

        if (is_subclass_of($class, CastsValue::class) || is_subclass_of($class, CastsAttributes::class)) {
            return empty($arguments) ? new $class : new $class(...$arguments);
        }

        throw new InvalidCastException(
            "Class {$class} must implement CastsValue, Castable, CastsAttributes, or be an enum."
        );
    }

    /**
     * Create an enum caster.
     *
     * @param  class-string<\UnitEnum>  $enumClass
     * @return \Illuminate\Contracts\Support\CastsValue<\UnitEnum>
     */
    protected function createEnumCaster(string $enumClass)
    {
        return new class($enumClass) implements CastsValue
        {
            public function __construct(
                protected string $enumClass
            ) {
            }

            public function cast(mixed $value, string $key, array $attributes)
            {
                if ($value instanceof $this->enumClass) {
                    return $value;
                }

                return is_subclass_of($this->enumClass, \BackedEnum::class)
                    ? ($this->enumClass)::from($value)
                    : constant($this->enumClass.'::'.$value);
            }
        };
    }

    /**
     * Apply cast using wildcard path matching.
     *
     * @param  array<string, mixed>  $data
     * @param  string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string  $cast
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
}
