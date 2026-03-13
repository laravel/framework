<?php

namespace Illuminate\Database\Events;

use Illuminate\Contracts\Database\Events\MigrationEvent as MigrationEventContract;
use Illuminate\Database\Migrations\Migration;

abstract class MigrationEvent implements MigrationEventContract
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Migration $migration,
        public string $method,
    ) {
    }
}
