<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Tests\Integration\Queue\JobRunRecorder;

class JobChainingBatchedJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public string $id;

    public int $times;

    public function __construct(string $id, int $times = 0)
    {
        $this->id = $id;
        $this->times = $times;
    }

    public function handle()
    {
        for ($i = 0; $i < $this->times; $i++) {
            $this->batch()->add(new JobChainingBatchedJob($this->id.'-'.$i));
        }
        JobRunRecorder::record($this->id);
    }
}
