<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Migrations\Migration;

class MigrationEnded extends MigrationEvent
{
    /**
     * The number of milliseconds the migration took to run.
     *
     * @var float|null
     */
    public $duration;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Migrations\Migration  $migration
     * @param  string  $method
     * @param  string|null  $name
     * @param  float|null  $duration
     */
    public function __construct(Migration $migration, $method, $name = null, $duration = null)
    {
        parent::__construct($migration, $method, $name);

        $this->duration = $duration;
    }
}
