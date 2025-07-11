<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;

class SavepointReleased extends ConnectionEvent
{
    public function __construct(
        Connection $connection,
        public string $savepoint
    ) {
        parent::__construct($connection);
    }
}
