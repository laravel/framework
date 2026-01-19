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
     * Set the type's default value.
     */
    public function default(int $value): static
    {
        $this->default = $value;

        return $this;
    }
}
