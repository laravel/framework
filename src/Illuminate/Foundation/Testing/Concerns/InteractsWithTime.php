<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\Wormhole;
use Illuminate\Support\Carbon;

trait InteractsWithTime
{
    /**
     * Begin travelling to another time.
     *
     * @param  int  $value
     * @return \Illuminate\Testing\Wormhole
     */
    public function travel($value)
    {
        return new Wormhole($value);
    }

    /**
     * Travel back to the current time.
     *
     * @return \DateTimeInterface
     */
    public function travelBack()
    {
        Carbon::setTestNow();

        return Carbon::now();
    }
}
