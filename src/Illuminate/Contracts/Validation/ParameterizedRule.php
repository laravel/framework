<?php

namespace Illuminate\Contracts\Validation;

use Stringable;

interface ParameterizedRule extends Stringable
{
    /**
     * Get the rule name followed by its parameters.
     *
     * @return array
     */
    public function toArray(): array;
}
