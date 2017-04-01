<?php

namespace Illuminate\Queue\Supervisor\Events;

use Illuminate\Queue\Supervisor\SupervisorOptions;
use Illuminate\Queue\Supervisor\SupervisorState;

abstract class Looping
{
    /**
     * @var SupervisorOptions
     */
    public $options;
    /**
     * @var SupervisorState
     */
    public $state;

    public function __construct(SupervisorOptions $options, SupervisorState $state)
    {
        $this->options = $options;
        $this->state = $state;
    }
}
