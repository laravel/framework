<?php

namespace Illuminate\JsonSchema;

use Closure;
use Illuminate\JsonSchema\Types\Type;

/**
 * @method static Types\ObjectType object(Closure|array<string, Types\Type> $properties = [])
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
