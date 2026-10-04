<?php

namespace Illuminate\Console\Scheduling\Sequences\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Console\Scheduling\Sequences\OccurrenceExecutor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ExecuteScheduledOccurrence implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 0;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(public int $occurrenceId)
    {
    }

    public function handle(OccurrenceExecutor $executor): void
    {
        if (! $executor->execute($this->occurrenceId)) {
            $this->release(30);
        }
    }
}
