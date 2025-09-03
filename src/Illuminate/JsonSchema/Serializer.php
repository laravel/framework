<?php

namespace Illuminate\JsonSchema;

class Serializer
{
    /**
     * The properties to ignore when serializing.
     *
     * @var array<int, string>
     */
    protected static array $ignore = ['required'];

    /**
     * Serialize the given property to an array.
     *
     * @return array<string, mixed>
     */
    public static function serialize(Types\Type $type): array
    {
        /** @var array<string, mixed> $attributes */
        $attributes = (fn () => get_object_vars($type))->call($type);

        $attributes['type'] = match (get_class($type)) {
            Types\ObjectType::class => 'object',
            Types\IntegerType::class => 'integer',
            Types\NumberType::class => 'number',
            Types\StringType::class => 'string',
            Types\BooleanType::class => 'boolean',
            Types\ArrayType::class => 'array',
            default => throw new \RuntimeException('Unsupported ['.get_class($type).'] type.'),
        };

        $attributes = array_filter($attributes, static function (mixed $value, string $key) {
            if (in_array($key, static::$ignore, true)) {
                return false;
            }

            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        if ($type instanceof Types\ObjectType) {
            // @phpstan-ignore-next-line
            if (count($attributes['properties']) === 0) {
                unset($attributes['properties']);
            } else {

                $required = array_keys(array_filter(
                    // @phpstan-ignore-next-line
                    $attributes['properties'],
                    // @phpstan-ignore-next-line
                    static fn (Types\Type $property) => static::isRequired($property),
                ));

                if (count($required) > 0) {
                    $attributes['required'] = $required;
                }

                $attributes['properties'] = array_map(
                    // @phpstan-ignore-next-line
                    static fn (Types\Type $property) => Serializer::serialize($property),
                    // @phpstan-ignore-next-line
                    $attributes['properties'],
                );
            }
        }

        if ($type instanceof Types\ArrayType) {
            if (isset($attributes['items']) && $attributes['items'] instanceof Types\Type) {
                $attributes['items'] = Serializer::serialize($attributes['items']);
            }
        }

        return $attributes;
    }

    /**
     * Determine if the given type is required.
     */
    private static function isRequired(Types\Type $type): bool
    {
        $attributes = (fn () => get_object_vars($type))->call($type);

        return isset($attributes['required']) && $attributes['required'] === true;
    }
}
