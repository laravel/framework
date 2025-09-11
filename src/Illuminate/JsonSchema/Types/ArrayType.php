<?php

namespace Illuminate\JsonSchema\Types;

class ArrayType extends Type
{
    /**
     * The minimum number of items (inclusive).
     */
    protected ?int $minItems = null;

    /**
     * The maximum number of items (inclusive).
     */
    protected ?int $maxItems = null;

    /**
     * The schema of the items contained in the array.
     */
    protected ?Type $items = null;

    /**
     * Set the minimum number of items (inclusive).
     */
    public function min(int $value): static
    {
        $this->minItems = $value;

        return $this;
    }

    /**
     * Set the maximum number of items (inclusive).
     */
    public function max(int $value): static
    {
        $this->maxItems = $value;

        return $this;
    }

    /**
     * Set the schema for array items.
     */
    public function items(Type $type): static
    {
        $this->items = $type;

        return $this;
    }

    /**
     * Set the type's default value.
     *
     * @param  array<int, mixed>  $value
     */
    public function default(array $value): static
    {
        $this->default = $value;

        return $this;
    }
}
