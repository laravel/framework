<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;

class SavepointRolledBack extends ConnectionEvent
{
    public function __construct(
        Connection $connection,
        public string $savepoint,
        public array $releasedSavepoints = []
    ) {
        parent::__construct($connection);
    }
}
