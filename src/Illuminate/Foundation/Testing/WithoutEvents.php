<?php

namespace Illuminate\Foundation\Testing;

use Exception;

trait WithoutEvents
{
    /**
     * Prevent all event handles from being executed.
     *
     * @throws \Exception
     */
    public function disableEventsForAllTests()
    {
        if (! method_exists($this, 'withoutEvents')) {
            throw new Exception('Unable to disable events. ApplicationTrait not used.');
        }

        $this->withoutEvents();
    }
}
