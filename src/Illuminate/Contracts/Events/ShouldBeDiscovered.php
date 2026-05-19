<?php

namespace Illuminate\Contracts\Events;

interface ShouldBeDiscovered
{
    /**
     * Determine if the listener should be registered during event discovery.
     */
    public function shouldBeDiscovered(): bool;
}
