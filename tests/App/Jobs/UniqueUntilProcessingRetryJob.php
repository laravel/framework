<?php

namespace Illuminate\Tests\App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UniqueUntilProcessingRetryJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public $tries = 2;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;

        if ($this->attempts() === 1) {
            throw new Exception('First attempt failure.');
        }
    }
}
