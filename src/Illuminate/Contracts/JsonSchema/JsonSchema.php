<?php

namespace Illuminate\Contracts\JsonSchema;

use Closure;

interface JsonSchema
{
    /**
     * Create a new object schema instance.
     *
     * @param  (Closure(JsonSchema): array<string, \Illuminate\JsonSchema\Types\Type>)|array<string, \Illuminate\JsonSchema\Types\Type>  $properties
     * @return \Illuminate\JsonSchema\Types\ObjectType
     */
    public function object(Closure|array $properties = []);

    /**
     * Create a new oneOf composite schema instance.
     *
     * @param  (Closure(JsonSchema): array<int, \Illuminate\JsonSchema\Types\Type>)|array<int, \Illuminate\JsonSchema\Types\Type>  $schemas
     * @return \Illuminate\JsonSchema\Types\CompositeType
     */
    public function oneOf(Closure|array $schemas);

    /**
     * Create a new anyOf composite schema instance.
     *
     * @param  (Closure(JsonSchema): array<int, \Illuminate\JsonSchema\Types\Type>)|array<int, \Illuminate\JsonSchema\Types\Type>  $schemas
     * @return \Illuminate\JsonSchema\Types\CompositeType
     */
    public function anyOf(Closure|array $schemas);

    /**
     * Create a new allOf composite schema instance.
     *
     * @param  (Closure(JsonSchema): array<int, \Illuminate\JsonSchema\Types\Type>)|array<int, \Illuminate\JsonSchema\Types\Type>  $schemas
     * @return \Illuminate\JsonSchema\Types\CompositeType
     */
    public function allOf(Closure|array $schemas);

    /**
     * Create a new const schema instance.
     *
     * @return \Illuminate\JsonSchema\Types\ConstType
     */
    public function const(mixed $value);

    /**
     * Create a new array property instance.
     *
     * @return \Illuminate\JsonSchema\Types\ArrayType
     */
    public function array();

    /**
     * Create a new string property instance.
     *
     * @return \Illuminate\JsonSchema\Types\StringType
     */
    public function string();

    /**
     * Create a new integer property instance.
     *
     * @return \Illuminate\JsonSchema\Types\IntegerType
     */
    public function integer();

    /**
     * Create a new number property instance.
     *
     * @return \Illuminate\JsonSchema\Types\NumberType
     */
    public function number();

    /**
     * Create a new boolean property instance.
     *
     * @return \Illuminate\JsonSchema\Types\BooleanType
     */
    public function boolean();
}
