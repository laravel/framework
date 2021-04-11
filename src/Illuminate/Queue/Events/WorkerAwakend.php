<?php

namespace Illuminate\Queue\Events;

class WorkerAwakend
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The the type of awakend event.
     *
     * @var int|null
     */
    public $awakendType;

    /**
     * Indicates if the event was fired because a new job was found.
     *
     * @var bool
     */
    public $awakendOnJobFound;

    /**
     * Create a new event instance.
     *
     * @param  string    $connectionName
     * @param  int|null  $awakendType
     * @param  bool      $awakendOnJobFound
     * @return void
     */
    public function __construct($connectionName, $awakendType = null, $awakendOnJobFound = false)
    {
        $this->connectionName = $connectionName;
        $this->awakendType = $awakendType;
        $this->awakendOnJobFound = $awakendOnJobFound;
    }
}
