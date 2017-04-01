<?php

namespace Illuminate\Queue\Supervisor;

class SupervisorState
{
    /**
     * Whether the supervisor is paused.
     *
     * @var bool
     */
    public $paused = false;

    /**
     * Whether the supervisor should quit.
     *
     * @var bool
     */
    public $shouldQuit = false;

    /**
     * @var int|null
     */
    public $lastRestart;
}
