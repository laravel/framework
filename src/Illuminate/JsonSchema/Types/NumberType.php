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
     * Sets the minimum value (inclusive).
     */
    public function min(int|float $value): static
    {
        $this->minimum = $value;

        return $this;
    }

    /**
     * Sets the maximum value (inclusive).
     */
    public function max(int|float $value): static
    {
        $this->maximum = $value;

        return $this;
    }

    /**
     * Sets the type's default value.
     */
    public function default(int|float $value): static
    {
        $this->default = $value;

        return $this;
    }
}
