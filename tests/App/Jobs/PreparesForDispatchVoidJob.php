<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\PreparesForDispatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PreparesForDispatchVoidJob implements PreparesForDispatch, ShouldQueue
{
    use Dispatchable, Queueable;

    public static bool $ran = false;

    public function prepareForDispatch(): void
    {
        static::$ran = true;
    }

    public function handle(): void
    {
    }
}
