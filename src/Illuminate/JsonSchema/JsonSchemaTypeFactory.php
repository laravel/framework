<?php

namespace Illuminate\JsonSchema;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema as JsonSchemaContract;

class JsonSchemaTypeFactory extends JsonSchema implements JsonSchemaContract
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
     * Create a new oneOf composite schema instance.
     *
     * @param  (Closure(JsonSchemaTypeFactory): array<int, Types\Type>)|array<int, Types\Type>  $schemas
     */
    public function oneOf(Closure|array $schemas): Types\CompositeType
    {
        return new Types\CompositeType(Types\CompositeType::ONE_OF, $this->resolveSchemas($schemas));
    }

    /**
     * Create a new anyOf composite schema instance.
     *
     * @param  (Closure(JsonSchemaTypeFactory): array<int, Types\Type>)|array<int, Types\Type>  $schemas
     */
    public function anyOf(Closure|array $schemas): Types\CompositeType
    {
        return new Types\CompositeType(Types\CompositeType::ANY_OF, $this->resolveSchemas($schemas));
    }

    /**
     * Create a new allOf composite schema instance.
     *
     * @param  (Closure(JsonSchemaTypeFactory): array<int, Types\Type>)|array<int, Types\Type>  $schemas
     */
    public function allOf(Closure|array $schemas): Types\CompositeType
    {
        return new Types\CompositeType(Types\CompositeType::ALL_OF, $this->resolveSchemas($schemas));
    }

    /**
     * Create a new const schema instance.
     */
    public function const(mixed $value): Types\ConstType
    {
        return new Types\ConstType($value);
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

    /**
     * Resolve the given schemas.
     *
     * @param  (Closure(JsonSchemaTypeFactory): array<int, Types\Type>)|array<int, Types\Type>  $schemas
     * @return array<int, Types\Type>
     */
    protected function resolveSchemas(Closure|array $schemas): array
    {
        if ($schemas instanceof Closure) {
            $schemas = $schemas($this);
        }

        return array_values($schemas);
    }
}
