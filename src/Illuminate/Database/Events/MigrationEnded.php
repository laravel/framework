<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Migrations\Migration;

class MigrationEnded extends MigrationEvent
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Migrations\Migration  $migration
     * @param  'down'|'up'  $method
     * @param  string|null  $name
     * @param  float|null  $duration  The number of milliseconds the migration took to run.
     */
    public function __construct(
        Migration $migration,
        $method,
        $name = null,
        public $duration = null,
    ) {
        parent::__construct($migration, $method, $name);
    }
}
