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
        if (method_exists($this, 'withoutEvents')) {
            $this->withoutEvents();
        } else {
            throw new Exception('Unable to disable middleware. ApplicationTrait not used.');
        }
    }
}
