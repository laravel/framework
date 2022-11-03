<?php

declare(strict_types=1);

namespace Illuminate\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Assert
{
    /**
     * @var array The rules to be applied to the property.
     */
    public readonly array $rules;

    /**
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }
}
