<?php

namespace Illuminate\Queue\Supervisor\Events;

use Illuminate\Queue\Supervisor\SupervisorOptions;
use Illuminate\Queue\Supervisor\SupervisorState;

class RunFailed extends Looping
{
    /**
     * @var \Throwable
     */
    public $exception;

    /**
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     * @param \Throwable $exception
     */
    public function __construct(SupervisorOptions $options, SupervisorState $state, $exception)
    {
        parent::__construct($options, $state);
        $this->exception = $exception;
    }
}
