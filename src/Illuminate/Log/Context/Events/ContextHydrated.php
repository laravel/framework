<?php

namespace Illuminate\Log\Context\Events;

class ContextHydrated
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Log\Context\Repository  $context
     */
    public function __construct(
        public $context,
    ) {
    }
}
