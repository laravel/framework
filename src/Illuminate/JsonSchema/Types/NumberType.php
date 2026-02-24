<?php

namespace Illuminate\JsonSchema\Types;

class NumberType extends Type
{
    /**
     * The minimum value (inclusive).
     */
    protected int|float|null $minimum = null;

    /**
     * The exclusive minimum value.
     */
    protected int|float|null $exclusiveMinimum = null;

    /**
     * The maximum value (inclusive).
     */
    protected int|float|null $maximum = null;

    /**
     * The exclusive maximum value.
     */
    protected int|float|null $exclusiveMaximum = null;


    /**
     * The number the value must be a multiple of.
     */
    protected int|float|null $multipleOf = null;

    /**
     * Set the minimum value.
     */
    public function min(int|float $value, bool $exclusive = false): static
    {
        $this->minimum = $exclusive ? null : $value;
        $this->exclusiveMinimum = $exclusive ? $value : null;

        return $this;
    }

    /**
     * Set the maximum value.
     */
    public function max(int|float $value, bool $exclusive = false): static
    {
        $this->maximum = $exclusive ? null : $value;
        $this->exclusiveMaximum = $exclusive ? $value : null;

        return $this;
    }

    /**
     * Set the number the value must be a multiple of.
     */
    public function multipleOf(int|float $value): static
    {
        $this->multipleOf = $value;

        return $this;
    }

    /**
     * Set the type's default value.
     */
    public function default(int|float $value): static
    {
        $this->default = $value;

        return $this;
    }
}
