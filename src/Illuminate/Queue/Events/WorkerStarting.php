<?php

namespace Illuminate\Queue\Events;

class WorkerStarting
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The the type of starting event.
     *
     * @var int
     */
    public $startingType;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  int     $startingType
     * @return void
     */
    public function __construct($connectionName, $startingType)
    {
        $this->connectionName = $connectionName;
        $this->startingType = $startingType;
    }
}
