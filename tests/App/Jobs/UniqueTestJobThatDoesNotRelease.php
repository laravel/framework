<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UniqueTestJobThatDoesNotRelease implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handled = false;
    public static $released = false;

    public function __construct()
    {
        static::$handled = false;
        static::$released = false;
    }

    public function handle()
    {
        static::$handled = true;
    }
}
