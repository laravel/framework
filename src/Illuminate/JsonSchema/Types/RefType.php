<?php

namespace Illuminate\JsonSchema\Types;

class RefType extends Type
{
    /**
     * Create a new reference type instance.
     */
    public function __construct(
        protected string $ref,
    ) {
        //
    }
}
