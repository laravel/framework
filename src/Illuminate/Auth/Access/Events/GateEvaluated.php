<?php

namespace Illuminate\Auth\Access\Events;

use Illuminate\Contracts\Auth\Authenticatable;

class GateEvaluated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public ?Authenticatable $user,
        public string $ability,
        public ?bool $result,
        public array $arguments,
    ) {
    }
}
