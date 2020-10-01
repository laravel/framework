<?php

namespace Illuminate\View;

class AppendableAttributeValue
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
