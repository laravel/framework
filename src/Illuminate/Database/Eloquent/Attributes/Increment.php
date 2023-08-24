<?php
namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Contracts\AttributesContract;
use Illuminate\Database\Eloquent\Model;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Increment implements AttributesContract
{
    public function __construct(public string $type = 'int')
    {

    }
}