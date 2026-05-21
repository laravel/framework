<?php

namespace Illuminate\JsonSchema\Types;

class ObjectType extends Type
{
    /**
     * The minimum number of properties (inclusive).
     */
    protected ?int $minProperties = null;

    /**
     * The maximum number of properties (inclusive).
     */
    protected ?int $maxProperties = null;

    /**
     * Whether additional properties are allowed.
     */
    protected ?bool $additionalProperties = null;

    /**
     * Create a new object type instance.
     *
     * @param  array<string, Type>  $properties
     */
    public function __construct(protected array $properties = [])
    {
        //
    }

    /**
     * Set the minimum number of properties (inclusive).
     */
    public function min(int $value): static
    {
        $this->minProperties = $value;

        return $this;
    }

    /**
     * Set the maximum number of properties (inclusive).
     */
    public function max(int $value): static
    {
        $this->maxProperties = $value;

        return $this;
    }

    /**
     * Disallow additional properties.
     */
    public function withoutAdditionalProperties(): static
    {
        $this->additionalProperties = false;

        return $this;
    }

    /**
     * Set the type's default value.
     *
     * @param  array<string, mixed>  $value
     */
    public function default(array $value): static
    {
        $this->default = $value;

        return $this;
    }
}
