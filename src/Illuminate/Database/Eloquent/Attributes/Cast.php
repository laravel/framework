<?php
namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Cast
{
    /**
     * Create a new attribute instance.
     */
    public function __construct(private string $property, private string|array $type)
    {
    }
}
