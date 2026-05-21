<?php

namespace Illuminate\JsonSchema;

use Closure;
use Illuminate\JsonSchema\Types\Type;

/**
 * @method static Types\ObjectType object(Closure|array<string, Types\Type> $properties = [])
 * @method static Types\CompositeType oneOf(Closure|array<int, Types\Type> $schemas)
 * @method static Types\CompositeType anyOf(Closure|array<int, Types\Type> $schemas)
 * @method static Types\CompositeType allOf(Closure|array<int, Types\Type> $schemas)
 * @method static Types\ConstType const(mixed $value)
 * @method static Types\IntegerType integer()
 * @method static Types\NumberType number()
 * @method static Types\StringType string()
 * @method static Types\BooleanType boolean()
 * @method static Types\ArrayType array()
 */
class JsonSchema
{
    /**
     * Dynamically pass static methods to the schema instance.
     */
    public static function __callStatic(string $name, mixed $arguments): Type
    {
        return (new JsonSchemaTypeFactory)->$name(...$arguments);
    }
}
