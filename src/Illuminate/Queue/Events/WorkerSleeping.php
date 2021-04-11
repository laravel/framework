<?php

namespace Illuminate\Queue\Events;

class WorkerSleeping
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The the type of sleeping event.
     *
     * @var int
     */
    public $sleepingType;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  int     $sleepingType
     * @return void
     */
    public function __construct($connectionName, $sleepingType)
    {
        $this->connectionName = $connectionName;
        $this->sleepingType = $sleepingType;
    }
}
