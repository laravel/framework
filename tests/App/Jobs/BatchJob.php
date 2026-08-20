<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Tests\Integration\Queue\BatchRunRecorder;

class BatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public static $results = [];

    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        BatchRunRecorder::record($this->id);
    }
}
