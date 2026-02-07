<?php

namespace Illuminate\Tests\Queue\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FakeSqsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}
