<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use RuntimeException;

class JobWillFail implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle()
    {
        throw new RuntimeException;
    }
}
