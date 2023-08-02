<?php

namespace Illuminate\Database\Events;

use Illuminate\Contracts\Database\Events\MigrationEvent as MigrationEventContract;

class DatabaseRefreshed implements MigrationEventContract
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $database
     * @param  bool  $needsSeeding
     * @return void
     */
    public function __construct(
        public ?string $database = null,
        public bool $needsSeeding = false
    ) {
        //
    }
}
