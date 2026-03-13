<?php

namespace Illuminate\Log\Context\Events;

use Illuminate\Log\Context\Repository;

class ContextHydrated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Repository $context,
    ) {
    }
}
