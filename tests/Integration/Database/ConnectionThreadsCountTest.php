<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\DB;

class ConnectionThreadsCountTest extends DatabaseTestCase
{
    public function testGetThreadsCount()
    {
        $count = DB::connection()->threadCount();

        if ($this->driver === 'sqlite') {
            $this->assertNull($count, 'SQLite does not support connection count');
        } else {
            $this->assertGreaterThanOrEqual(1, $count);
        }
    }
}
