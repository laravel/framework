<?php

namespace Illuminate\JsonSchema\Types;

class ObjectType extends Type
{
    /**
     * The additional properties constraint.
     */
    protected bool|Type|null $additionalProperties = null;

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
     * Set the additional properties constraint.
     */
    public function additionalProperties(bool|Type $value = true): static
    {
        $this->additionalProperties = $value === true ? null : $value;

        return $this;
    }

    /**
     * Disallow additional properties.
     */
    public function withoutAdditionalProperties(): static
    {
        return $this->additionalProperties(false);
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
