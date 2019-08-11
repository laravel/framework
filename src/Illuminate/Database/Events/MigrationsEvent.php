<?php

namespace Illuminate\Database\Events;

use Illuminate\Contracts\Database\Events\MigrationEvent as MigrationEventContract;

abstract class MigrationsEvent implements MigrationEventContract
{
    /**
     * An array containing the migration objects.
     *
     * @var array
     */
    public $migrations = [];

    /**
     * The migration method that was called.
     *
     * @var string
     */
    public $method;

    /**
     * Create a new event instance.
     *
     * @param  array  $migrations
     * @param  string  $method
     * @return void
     */
    public function __construct(array $migrations, $method)
    {
        $this->method = $method;
        $this->migrations = $migrations;
    }
}
