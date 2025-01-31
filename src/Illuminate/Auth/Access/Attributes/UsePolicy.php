<?php

namespace Illuminate\Auth\Access\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UsePolicy
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string  $policyClass
     * @return void
     */
    public function __construct(public string $policyClass)
    {
    }
}
