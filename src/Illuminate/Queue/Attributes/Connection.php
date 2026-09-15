<?php

namespace Illuminate\Queue\Attributes;

use Attribute;
use Closure;
use UnitEnum;

use function Illuminate\Support\enum_value;

#[Attribute(Attribute::TARGET_CLASS)]
class Connection
{
    /**
     * Create a new attribute instance.
     *
     * @param  \UnitEnum|string|\Closure(\Illuminate\Contracts\Container\Container): (\UnitEnum|string)  $connection
     */
    public function __construct(public UnitEnum|string|Closure $connection)
    {
        $this->connection = $connection instanceof Closure ? $connection : enum_value($connection);
    }
}
