<?php

namespace Illuminate\JsonSchema;

use InvalidArgumentException;
use RuntimeException;

class Serializer
{
    /**
     * The properties to ignore when serializing.
     *
     * @var array<int, string>
     */
    protected static array $ignore = ['required', 'nullable', 'definition'];

    /**
     * Serialize the given property to an array.
     *
     * Types registered with "define()" are hoisted into the root "$defs" and replaced by a "$ref" pointer.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function serialize(Types\Type $type): array
    {
        $definitions = ['schemas' => [], 'types' => []];

        $schema = static::serializeType($type, $definitions, inline: true);

        if ($definitions['schemas'] !== []) {
            $schema['$defs'] = $definitions['schemas'];
        }

        return $schema;
    }

    /**
     * Serialize the given type, collecting any named definitions it contains.
     *
     * @param  array{schemas: array<string, array<string, mixed>>, types: array<string, \Illuminate\JsonSchema\Types\Type>}  $definitions
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected static function serializeType(Types\Type $type, array &$definitions, bool $inline = false): array
    {
        /** @var array<string, mixed> $attributes */
        $attributes = (fn () => get_object_vars($type))->call($type);

        if (is_string($name = $attributes['definition'] ?? null)) {
            static::define($name, $type, $definitions);

            if (! $inline) {
                return ['$ref' => static::pointer($name)];
            }
        }

        if ($type instanceof Types\RefType) {
            $reference = ['$ref' => static::pointer($attributes['ref'])];

            unset($attributes['ref']);

            $attributes = static::filter($attributes);

            return static::isNullable($type)
                ? [...$attributes, 'anyOf' => [$reference, ['type' => 'null']]]
                : [...$reference, ...$attributes];
        }

        if ($type instanceof Types\AnyOfType) {
            $attributes['anyOf'] = [];

            foreach ($attributes['schemas'] as $schema) {
                $attributes['anyOf'][] = static::serializeType($schema, $definitions);
            }

            unset($attributes['schemas']);

            if (static::isNullable($type)) {
                $attributes['anyOf'][] = ['type' => 'null'];
            }

            return static::filter($attributes);
        }

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

        unset($attributes['types']);

        $nullable = static::isNullable($type);

        if ($nullable) {
            $attributes['type'] = is_array($attributes['type'])
                ? [...$attributes['type'], 'null']
                : [$attributes['type'], 'null'];
        }

        $attributes = static::filter($attributes);

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

                foreach ($attributes['properties'] as $key => $property) {
                    $attributes['properties'][$key] = static::serializeType($property, $definitions);
                }
            }
        }

        if ($type instanceof Types\ArrayType) {
            if (isset($attributes['items']) && $attributes['items'] instanceof Types\Type) {
                $attributes['items'] = static::serializeType($attributes['items'], $definitions);
            }
        }

        return $attributes;
    }

    /**
     * Register the given type as a named definition.
     *
     * @param  array{schemas: array<string, array<string, mixed>>, types: array<string, \Illuminate\JsonSchema\Types\Type>}  $definitions
     *
     * @throws \InvalidArgumentException
     */
    protected static function define(string $name, Types\Type $type, array &$definitions): void
    {
        if (isset($definitions['types'][$name])) {
            if ($definitions['types'][$name] !== $type) {
                throw new InvalidArgumentException("The schema definition [{$name}] has already been defined.");
            }

            return;
        }

        // Reserve the name before serializing so the definition keeps its position...
        $definitions['types'][$name] = $type;
        $definitions['schemas'][$name] = [];

        $definitions['schemas'][$name] = static::serializeType($type, $definitions, inline: true);
    }

    /**
     * Get the JSON pointer to the given definition.
     */
    protected static function pointer(string $name): string
    {
        return '#/$defs/'.str_replace(['~', '/'], ['~0', '~1'], $name);
    }

    /**
     * Remove ignored and empty attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function filter(array $attributes): array
    {
        return array_filter($attributes, static function (mixed $value, string $key) {
            if (in_array($key, static::$ignore, true)) {
                return false;
            }

            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);
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
