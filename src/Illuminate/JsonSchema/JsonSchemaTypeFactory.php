<?php

namespace Illuminate\JsonSchema;

use Closure;

class JsonSchemaTypeFactory extends JsonSchema
{
    /**
     * Create a new object schema instance.
     *
     * @param  (Closure(JsonSchemaTypeFactory): array<string, Types\Type>)|array<string, Types\Type>  $properties
     */
    public function object(Closure|array $properties = []): Types\ObjectType
    {
        if ($properties instanceof Closure) {
            $properties = $properties($this);
        }

        return new Types\ObjectType($properties);
    }

    /**
     * Create a new array property instance.
     */
    public function array(): Types\ArrayType
    {
        return new Types\ArrayType;
    }

    /**
     * Create a new string property instance.
     */
    public function string(): Types\StringType
    {
        return new Types\StringType;
    }

    /**
     * Create a new integer property instance.
     */
    public function integer(): Types\IntegerType
    {
        return new Types\IntegerType;
    }

    /**
     * Create a new number property instance.
     */
    public function number(): Types\NumberType
    {
        return new Types\NumberType;
    }

    /**
     * Create a new boolean property instance.
     */
    public function boolean(): Types\BooleanType
    {
        return new Types\BooleanType;
    }
}
