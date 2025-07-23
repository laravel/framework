<?php

namespace Illuminate\Container\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Concrete
{
    /**
     * @param  string  $class  The class to be used for the contract, should a binding not exist.
     */
    public function __construct(public $class) {}
}
