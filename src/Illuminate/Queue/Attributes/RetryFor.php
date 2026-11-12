<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RetryFor
{
    /**
     * The number of seconds after being queued during which the queueable may be retried.
     *
     * @var int
     */
    public int $retryFor;

    /**
     * Create a new attribute instance.
     *
     * @param  int  $seconds  Seconds to keep retrying the queueable for.
     * @param  int  $minutes  Minutes to keep retrying the queueable for.
     * @param  int  $hours  Hours to keep retrying the queueable for.
     */
    public function __construct(int $seconds = 0, int $minutes = 0, int $hours = 0)
    {
        $this->retryFor = $seconds + ($minutes * 60) + ($hours * 3600);
    }
}
