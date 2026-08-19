<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DebounceFor
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $debounceFor  Seconds to debounce the job for.
     * @param  int|null  $maxWait  The maximum number of seconds the job can be deferred before it is forced to run.
     */
    public function __construct(public int $debounceFor, public ?int $maxWait = null)
    {
        //
    }
}
