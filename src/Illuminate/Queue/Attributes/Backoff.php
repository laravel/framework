<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Backoff
{
    /**
     * The backoff values.
     *
     * @var array<int>|int
     */
    public array|int $backoff;

    /**
     * Create a new attribute instance.
     *
     * @param  array<int>|int  ...$backoff
     */
    public function __construct(array|int ...$backoff)
    {
        $this->backoff = count($backoff) === 1 ? $backoff[0] : $backoff;
    }
}
