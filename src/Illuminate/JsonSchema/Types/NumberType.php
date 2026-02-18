<?php

namespace Illuminate\JsonSchema\Types;

class NumberType extends Type
{
    /**
     * The minimum value (inclusive).
     */
    protected int|float|null $minimum = null;

    /**
     * The maximum value (inclusive).
     */
    protected int|float|null $maximum = null;

    /**
     * The minimum value (exclusive).
     */
    protected int|float|null $exclusiveMinimum = null;

    /**
     * The maximum value (exclusive).
     */
    protected int|float|null $exclusiveMaximum = null;

    /**
     * Set the minimum value (inclusive).
     */
    public function min(int|float $value): static
    {
        $this->minimum = $value;

        return $this;
    }

    /**
     * Set the maximum value (inclusive).
     */
    public function max(int|float $value): static
    {
        $this->maximum = $value;

        return $this;
    }

    /**
     * Set the minimum value (exclusive).
     */
    public function exclusiveMin(int|float $value): static
    {
        $this->exclusiveMinimum = $value;

        return $this;
    }

    /**
     * Set the maximum value (exclusive).
     */
    public function exclusiveMax(int|float $value): static
    {
        $this->exclusiveMaximum = $value;

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
