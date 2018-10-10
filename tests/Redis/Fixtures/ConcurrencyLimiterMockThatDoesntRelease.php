<?php

namespace Illuminate\Tests\Redis\Fixtures;

use Illuminate\Redis\Limiters\ConcurrencyLimiter;

class ConcurrencyLimiterMockThatDoesntRelease extends ConcurrencyLimiter
{
    protected function release($Key)
    {
        //
    }
}
