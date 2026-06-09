<?php

namespace Illuminate\JsonSchema;

use RuntimeException;

class Serializer
{
    /**
     * The properties to ignore when serializing.
     *
     * @var array<int, string>
     */
    protected static array $ignore = ['required', 'nullable'];

    /**
     * Serialize the given property to an array.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public static function serialize(Types\Type $type): array
    {
        /** @var array<string, mixed> $attributes */
        $attributes = (fn () => get_object_vars($type))->call($type);

        $attributes['type'] = match (get_class($type)) {
            Types\ArrayType::class => 'array',
            Types\BooleanType::class => 'boolean',
            Types\IntegerType::class => 'integer',
            Types\NumberType::class => 'number',
            Types\ObjectType::class => 'object',
            Types\StringType::class => 'string',
            Types\UnionType::class => $attributes['types'],
            default => throw new RuntimeException('Unsupported ['.get_class($type).'] type.'),
        };

        // The member names are emitted as the "type" array, so drop the backing property...
        unset($attributes['types']);

        $nullable = static::isNullable($type);

        if ($nullable) {
            $names = is_array($attributes['type']) ? $attributes['type'] : [$attributes['type']];

            if (! in_array('null', $names, true)) {
                $names[] = 'null';
            }

            $attributes['type'] = $names;
        }

        $attributes = array_filter($attributes, static function (mixed $value, string $key) {
            if (in_array($key, static::$ignore, true)) {
                return false;
            }

            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        if ($type instanceof Types\ObjectType) {
            if (count($attributes['properties']) === 0) {
                unset($attributes['properties']);
            } else {
                $required = array_map(
                    'strval',
                    array_keys(array_filter(
                        $attributes['properties'],
                        static fn (Types\Type $property) => static::isRequired($property),
                    ))
                );

                if ($required !== []) {
                    $attributes['required'] = $required;
                }

                $attributes['properties'] = array_map(
                    static fn (Types\Type $property) => static::serialize($property),
                    $attributes['properties'],
                );
            }
        }

        if ($type instanceof Types\ArrayType) {
            if (isset($attributes['items']) && $attributes['items'] instanceof Types\Type) {
                $attributes['items'] = static::serialize($attributes['items']);
            }
        }

        return $attributes;
    }

    /**
     * Determine if the given type is required.
     */
    protected static function isRequired(Types\Type $type): bool
    {
        $attributes = (fn () => get_object_vars($type))->call($type);

        return isset($attributes['required']) && $attributes['required'] === true;
    }

    /**
     * Determine if the given type is nullable.
     */
    protected static function isNullable(Types\Type $type): bool
    {
        $attributes = (fn () => get_object_vars($type))->call($type);

        return isset($attributes['nullable']) && $attributes['nullable'] === true;
    }
}
