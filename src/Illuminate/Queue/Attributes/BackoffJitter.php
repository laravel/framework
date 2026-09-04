<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class BackoffJitter
{
    /**
     * The default proportion the backoff may be adjusted by.
     */
    public const DEFAULT_RATIO = 0.25;

    /**
     * Create a new attribute instance.
     *
     * @param  float  $ratio  Proportion of the backoff the delay may vary by, between 0 and 1.
     */
    public function __construct(public float $ratio = self::DEFAULT_RATIO)
    {
        //
    }
}
