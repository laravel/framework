<?php

namespace Illuminate\Database\Events;

use Illuminate\Contracts\Database\Events\MigrationEvent as MigrationEventContract;

class MigrationsStarting implements MigrationEventContract
{
    /**
     * An array of paths used by the migrator
     *
     * @var array
     */
    public $paths;

    /**
     * An array of options used by the migrator
     *
     * @var array
     */
    public $options;

    /**
     * Create a new MigrationsStarting instance.
     *
     * @param  array  $paths
     * @param  array  $options
     * @return void
     */
    public function __construct(array &$paths, array &$options)
    {
        $this->paths = $paths;
        $this->options = $options;
    }
}
