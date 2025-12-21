<?php

namespace Illuminate\Tests\Integration\Database\Queue\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class TimeOutNonBatchableJobWithNestedTransactions implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 2;

    public function handle(): void
    {
        DB::transaction(function () {
            DB::transaction(fn () => sleep(20));
        });
    }
}
