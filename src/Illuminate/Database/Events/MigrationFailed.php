<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Migrations\Migration;
use Throwable;

class MigrationFailed extends MigrationEvent
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Migrations\Migration  $migration
     * @param  string  $method
     * @param  string|null  $name
     * @param  \Throwable  $exception  The exception that caused the migration to fail.
     */
    public function __construct(
        Migration $migration,
        $method,
        $name,
        public Throwable $exception,
    ) {
        parent::__construct($migration, $method, $name);
    }
}
