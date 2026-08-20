<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;

class JobChainAddingAddedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var Carbon|null */
    public static $ranAt = null;

    public function handle()
    {
        static::$ranAt = Carbon::now();
    }
}
