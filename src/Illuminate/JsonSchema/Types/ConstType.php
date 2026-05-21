<?php

namespace Illuminate\JsonSchema\Types;

class ConstType extends Type
{
    /**
     * Create a new const type instance.
     */
    public function __construct(mixed $value)
    {
        $this->const($value);
    }
}
