<?php
namespace Illuminate\Database\Eloquent\Attributes;

use Illuminate\Database\Eloquent\Contracts\AttributesContract;
use Illuminate\Database\Eloquent\Model;

#[Attribute(Attribute::TARGET_METHOD)]
class Append implements AttributesContract
{
    public function __contruct($key, Model $model)
    {

    }
}