<?php

namespace Illuminate\Auth\Access\Events;

class GateEvaluated
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string  $ability
     * @param  bool|null  $result
     * @param  array  $arguments
     * @return void
     */
    public function __construct(
        public $user,
        public $ability,
        public $result,
        public $arguments,
    ) {
    }
}
