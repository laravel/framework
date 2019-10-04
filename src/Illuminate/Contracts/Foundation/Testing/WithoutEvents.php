<?php

namespace Illuminate\Contracts\Foundation\Testing;

interface WithoutEvents
{
    /**
     * Prevent all event handles from being executed.
     *
     * @throws \Exception
     */
    public function disableEventsForAllTests();
}
