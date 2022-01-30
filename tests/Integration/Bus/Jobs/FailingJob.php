<?php

namespace Illuminate\Tests\Integration\Bus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class FailingJob
{
    use InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->fail();
    }
}
