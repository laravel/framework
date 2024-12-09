<?php

namespace Illuminate\Log\Context\Events;

class ContextDehydrating
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
