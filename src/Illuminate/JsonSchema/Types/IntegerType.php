<?php

namespace Illuminate\JsonSchema\Types;

class IntegerType extends Type
{
    /**
     * The minimum value (inclusive).
     */
    protected ?int $minimum = null;

    /**
     * The exclusive minimum value.
     */
    protected ?int $exclusiveMinimum = null;

    /**
     * The maximum value (inclusive).
     */
    protected ?int $maximum = null;

    /**
     * The exclusive maximum value.
     */
    protected ?int $exclusiveMaximum = null;

    /**
     * The number the value must be a multiple of.
     */
    protected ?int $multipleOf = null;

    /**
     * Set the minimum value.
     */
    public function min(int $value, bool $exclusive = false): static
    {
        $this->minimum = $exclusive ? null : $value;
        $this->exclusiveMinimum = $exclusive ? $value : null;

        return $this;
    }

    /**
     * Set the maximum value.
     */
    public function max(int $value, bool $exclusive = false): static
    {
        $this->maximum = $exclusive ? null : $value;
        $this->exclusiveMaximum = $exclusive ? $value : null;

        return $this;
    }

    /**
     * Set the number the value must be a multiple of.
     */
    public function multipleOf(int $value): static
    {
        $this->multipleOf = $value;

        return $this;
    }

    /**
     * Set the type's default value.
     */
    public function default(int $value): static
    {
        $this->default = $value;

        return $this;
    }
}
