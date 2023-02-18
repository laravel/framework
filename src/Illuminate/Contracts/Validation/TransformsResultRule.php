<?php
declare(strict_types=1);

namespace Illuminate\Contracts\Validation;

interface TransformsResultRule
{
    /**
     * @param string $attribute the name of the attribute we are validating.
     * @param mixed $value The value the attribute currently has
     * @param array $context data context we are validating in (all other values), which will be updated as validation rules being applied.
     * @return mixed The value we want to set for the attribute
     */
    public function transform(string $attribute, mixed $value, array $context): mixed;
}
