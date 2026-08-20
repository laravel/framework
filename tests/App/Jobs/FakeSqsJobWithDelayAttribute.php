<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Delay;

#[Delay(15)]
class FakeSqsJobWithDelayAttribute implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}
