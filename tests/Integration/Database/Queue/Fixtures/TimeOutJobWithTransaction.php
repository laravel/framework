<?php

namespace Illuminate\Tests\Integration\Database\Queue\Fixtures;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class TimeOutJobWithTransaction implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Batchable;

    public int $tries = 1;
    public int $timeout = 2;

    public function handle(): void
    {
        DB::transaction(fn () => sleep(20));
    }
}
