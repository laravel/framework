<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Migrations\Migrator;

abstract class MigrationEvent
{
    public $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }
}
