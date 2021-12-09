<?php

namespace Illuminate\Foundation\Testing;

use Exception;
use Illuminate\Support\Hooks\Hook;

trait WithoutEvents
{
    /**
     * Register test case hook.
     *
     * @return \Illuminate\Support\Hooks\Hook
     */
    public function registerWithoutEventsHook(): Hook
    {
        return new Hook('setUp', fn () => $this->disableEventsForAllTests(), 70);
    }

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
            throw new Exception('Unable to disable events. ApplicationTrait not used.');
        }
    }
}
