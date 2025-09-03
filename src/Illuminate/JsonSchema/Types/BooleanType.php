<?php

namespace Illuminate\JsonSchema\Types;

class BooleanType extends Type
{
    /**
     * Sets the type's default value.
     */
    public function default(bool $value): static
    {
        $this->default = $value;

        return $this;
    }
}
