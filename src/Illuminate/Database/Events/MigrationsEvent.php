<?php

namespace Illuminate\Database\Events;

use Illuminate\Contracts\Database\Events\MigrationEvent as MigrationEventContract;

abstract class MigrationsEvent implements MigrationEventContract
{
    /**
     * Create a new event instance.
     *
     * @param  string  $method  The migration method that was invoked.
     * @return void
     */
    public function __construct(
        public $method,
    ) {
    }
}
