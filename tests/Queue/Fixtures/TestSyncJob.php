<?php

namespace Illuminate\Tests\Queue\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TestSyncJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        //
    }
}
