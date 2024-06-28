<?php
namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Cast
{
    public function __construct(private string $property, private string|array $type)
    {
    }
}
