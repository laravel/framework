<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Tests\Integration\Queue\BatchRunRecorder;

class FailingJob extends BatchJob
{
    public function handle()
    {
        BatchRunRecorder::recordFailure($this->id);
        $this->fail();
    }
}
