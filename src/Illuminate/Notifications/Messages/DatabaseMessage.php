<?php

namespace Illuminate\Notifications\Messages;

class DatabaseMessage
{
    /**
     * Create a new database message.
     */
    public function __construct(
        public array $data = [],
    ) {
    }
}
