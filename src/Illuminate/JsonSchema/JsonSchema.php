<?php

namespace Illuminate\JsonSchema;

use Closure;
use Illuminate\JsonSchema\Types\Type;

/**
 * @method static Types\ObjectType object(Closure|array<string, Types\Type> $properties = [])
 * @method static Types\AnyOfType anyOf(Closure|array<int, Types\Type> $schemas)
 * @method static Types\IntegerType integer()
 * @method static Types\NumberType number()
 * @method static Types\StringType string()
 * @method static Types\BooleanType boolean()
 * @method static Types\ArrayType array()
 * @method static Types\UnionType union(array<int, string> $types)
 */
class JsonSchema
{
    /**
     * Build a type from a raw array of the Laravel-supported JSON Schema subset.
     *
     * @param  array<string, mixed>  $schema
     *
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $schema): Type
    {
        return Deserializer::deserialize($schema);
    }

    /**
     * Build a schema from a set of Laravel validation rules.
     *
     * Rules without a JSON Schema equivalent are ignored.
     *
     * @param  array<string, mixed>  $rules
     */
    public static function fromRules(array $rules): Types\ObjectType
    {
        return RuleDeserializer::deserialize($rules);
    }

    /**
     * Dynamically pass static methods to the schema instance.
     */
    public static function __callStatic(string $name, mixed $arguments): Type
    {
        return (new JsonSchemaTypeFactory)->$name(...$arguments);
    }
}
