<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Tests\Integration\Queue\JobRunRecorder;

class JobChainAddingAppendingBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        $this->appendToChain(Bus::batch([
            new JobChainingNamedTestJob('b1'),
            new JobChainingNamedTestJob('b2'),
        ]));

        JobRunRecorder::record($this->id);
    }
}
