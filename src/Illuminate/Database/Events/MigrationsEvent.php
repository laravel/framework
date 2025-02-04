<?php

namespace Illuminate\Database\Events;

use Illuminate\Contracts\Database\Events\MigrationEvent as MigrationEventContract;

abstract class MigrationsEvent implements MigrationEventContract
{
    /**
     * The migration method that was invoked.
     *
     * @var string
     */
    public $method;

    /**
     * The options provided when the migration method was invoked.
     *
     * @var array<string, mixed>
     */
    public $options;

    /**
     * Create a new event instance.
     *
     * @param  string  $method
     * @param  array<string, mixed>  $options
     * @return void
     */
    public function __construct($method, array $options = [])
    {
        $this->method = $method;
        $this->options = $options;
    }
}
