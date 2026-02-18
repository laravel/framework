<?php

namespace Illuminate\JsonSchema\Types;

class IntegerType extends Type
{
    /**
     * The minimum value (inclusive).
     */
    protected ?int $minimum = null;

    /**
     * The maximum value (inclusive).
     */
    protected ?int $maximum = null;

    /**
     * The minimum value (exclusive).
     */
    protected ?int $exclusiveMinimum = null;

    /**
     * The maximum value (exclusive).
     */
    protected ?int $exclusiveMaximum = null;

    /**
     * Set the minimum value (inclusive).
     */
    public function min(int $value): static
    {
        $this->minimum = $value;

        return $this;
    }

    /**
     * Set the maximum value (inclusive).
     */
    public function max(int $value): static
    {
        $this->maximum = $value;

        return $this;
    }

    /**
     * Set the minimum value (exclusive).
     */
    public function exclusiveMin(int $value): static
    {
        $this->exclusiveMinimum = $value;

        return $this;
    }

    /**
     * Set the maximum value (exclusive).
     */
    public function exclusiveMax(int $value): static
    {
        $this->exclusiveMaximum = $value;

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
