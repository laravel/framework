<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Fillable
{
    /**
     * @var array<int, string>
     */
    public array $columns;

    /**
     * Create a new attribute instance.
     *
     * @param  array<int, string>|string  ...$columns
     */
    public function __construct(array|string ...$columns)
    {
        $this->columns = is_array($columns[0]) ? $columns[0] : $columns;
    }
}
