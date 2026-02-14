<?php

namespace Illuminate\Foundation\Http\Concerns;

use BackedEnum;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Foundation\Http\TypedFormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use ReflectionNamedType;
use ReflectionUnionType;
use stdClass;

trait CastsValidatedData
{
    /**
     * Cast validated data into constructor arguments for the request class.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    protected function castValidatedData(array $validated): array
    {
        if (($constructor = $this->reflectRequest()->getConstructor()) === null) {
            return $validated;
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $param) {
            $fieldName = $this->fieldNameFor($param);
            $name = $param->getName();

            if (! Arr::has($validated, $fieldName)) {
                continue;
            }

            $value = Arr::get($validated, $fieldName);

            if ($value === null) {
                $arguments[$name] = null;

                continue;
            }

            $type = $param->getType();

            if ($type instanceof ReflectionUnionType) {
                $nestedRequestClass = $this->nestedHydrationClassFromUnion($type, $param);

                if ($nestedRequestClass !== null && is_array($value)) {
                    $arguments[$name] = $this->instantiateFromValidatedArray(
                        $nestedRequestClass,
                        $this->ensureArrayValue($fieldName, $value)
                    );
                } else {
                    $arguments[$name] = $value;
                }

                continue;
            }

            if (! $type instanceof ReflectionNamedType) {
                $arguments[$name] = $value;

                continue;
            }

            $typeName = $type->getName();
            if ($type->isBuiltin()) {
                if ($typeName === 'object') {
                    $arguments[$name] = $this->castBuiltinObjectValue($value);
                } else {
                    $arguments[$name] = $value;
                }

                continue;
            }

            if ($this->isDateObjectType($typeName)) {
                $arguments[$name] = $this->castDateValue($typeName, $value);
            } elseif (is_a($typeName, stdClass::class, true)) {
                $arguments[$name] = $this->castBuiltinObjectValue($value);
            } elseif ($this->shouldHydrateParameter($param, $typeName) || is_subclass_of($typeName, TypedFormRequest::class)) {
                $arguments[$name] = $this->instantiateFromValidatedArray(
                    $typeName,
                    $this->ensureArrayValue($fieldName, $value)
                );
            } elseif (is_subclass_of($typeName, BackedEnum::class)) {
                $arguments[$name] = $typeName::from($value);
            } elseif (is_a($typeName, Collection::class, true)) {
                if ($value instanceof $typeName) {
                    $arguments[$name] = $value;
                } else {
                    $arguments[$name] = new $typeName($this->ensureArrayValue($fieldName, $value));
                }
            } else {
                $arguments[$name] = $value;
            }
        }

        return $arguments;
    }

    /**
     * Cast an "object" builtin value into a stdClass instance when appropriate.
     */
    protected function castBuiltinObjectValue(mixed $value): mixed
    {
        return is_array($value) ? (object) $value : $value;
    }

    /**
     * Cast the given value to the requested date object type.
     *
     * @param  class-string  $typeName  The date object class name.
     * @param  mixed  $value  The validated value.
     */
    protected function castDateValue(string $typeName, mixed $value): ?DateTimeInterface
    {
        if ($value === null || ($value instanceof DateTimeInterface && $value instanceof $typeName)) {
            return $value;
        }

        $parsed = Date::parse($value);

        return match (true) {
            $typeName === DateTimeInterface::class => $parsed,
            $typeName === DateTime::class => $parsed->toDateTime(),
            $typeName === DateTimeImmutable::class => $parsed->toDateTimeImmutable(),
            is_a($typeName, CarbonImmutable::class, true) => CarbonImmutable::instance($parsed),
            default => $parsed,
        };
    }

    /**
     * Determine if the given class name is a date object type.
     *
     * @param  class-string  $name
     */
    protected function isDateObjectType(string $name): bool
    {
        return is_a($name, DateTimeInterface::class, true);
    }
}
