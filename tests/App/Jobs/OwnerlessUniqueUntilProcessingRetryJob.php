<?php

namespace Illuminate\Tests\App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class OwnerlessUniqueUntilProcessingRetryJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use InteractsWithQueue, Dispatchable;

    public $tries = 2;

    public function handle()
    {
        if ($this->attempts() === 1) {
            throw new Exception('First attempt failure.');
        }
    }
}
