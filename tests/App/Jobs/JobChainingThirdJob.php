<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class JobChainingThirdJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public static $usedQueue = null;

    public static $usedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
    }
}
